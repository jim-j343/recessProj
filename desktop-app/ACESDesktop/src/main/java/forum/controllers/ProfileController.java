package forum.controllers;

import forum.api.ApiClient;
import forum.app.SceneManager;
import forum.app.Session;
import forum.models.Role;
import forum.models.User;
import forum.services.AuthService;
import forum.util.NavbarHelper;

import javafx.application.Platform;
import javafx.fxml.FXML;
import javafx.scene.control.Alert;
import javafx.scene.control.ButtonType;
import javafx.scene.control.Label;
import javafx.scene.control.MenuButton;
import javafx.scene.control.PasswordField;
import javafx.scene.control.TextField;
import javafx.stage.FileChooser;
import java.io.File;
import java.util.Optional;

public class ProfileController {

    // ── Navbar ──────────────────────────────────────────────────────────────
    @FXML private Label      avatarLabel;
    @FXML private Label      userNameLabel;
    @FXML private MenuButton notifButton;
    @FXML private Label      notifBadge;
    @FXML private Label      navMyProgress;
    @FXML private Label      navNewTopic;
    @FXML private Label      navQuizCenter;
    @FXML private Label      navGrading;

    // ── Profile Information form ─────────────────────────────────────────────
    @FXML private TextField usernameField;
    @FXML private TextField emailField;
    @FXML private Label     saveStatusLabel;
    @FXML private Label     profileAvatarPreview;
    @FXML private Label     chosenFileLabel;
    private File            selectedAvatarFile;

    // ── Update Password form ─────────────────────────────────────────────────
    @FXML private PasswordField currentPasswordField;
    @FXML private PasswordField newPasswordField;
    @FXML private PasswordField confirmPasswordField;
    @FXML private Label         passwordStatusLabel;

    private final ApiClient api = new ApiClient();

    @FXML
    private void initialize() {
        User u = Session.currentUser();
        if (u != null) {
            // Navbar
            if (avatarLabel != null)   avatarLabel.setText(initial(u.displayName()));
            if (userNameLabel != null) userNameLabel.setText(u.displayName());

            // Profile form — populate with real credentials from session
            if (usernameField != null) usernameField.setText(u.getUsername() != null ? u.getUsername() : "");
            if (emailField != null)    emailField.setText(u.getEmail() != null ? u.getEmail() : "");
            if (profileAvatarPreview != null) profileAvatarPreview.setText(initial(u.displayName()));

            // Role-based nav tabs — matches web layouts/navigation.blade.php
            if (u.getRole() == Role.STUDENT && navMyProgress != null) {
                navMyProgress.setManaged(true); navMyProgress.setVisible(true);
            }
            if (u.getRole() != Role.SYSTEM_ADMIN && navNewTopic != null) {
                navNewTopic.setManaged(true); navNewTopic.setVisible(true);
            }
            if (u.getRole() == Role.LECTURER && navQuizCenter != null && navGrading != null) {
                navQuizCenter.setManaged(true); navQuizCenter.setVisible(true);
                navGrading.setManaged(true); navGrading.setVisible(true);
            }
        }

        // Load real notifications from backend
        NavbarHelper.loadNotifications(api, notifButton, notifBadge);
    }

    /** Choose a profile picture file from disk. */
    @FXML
    private void onChooseAvatar() {
        FileChooser chooser = new FileChooser();
        chooser.setTitle("Select Profile Picture");
        chooser.getExtensionFilters().addAll(
                new FileChooser.ExtensionFilter("Image Files (*.jpg, *.jpeg, *.png, *.webp)", "*.jpg", "*.jpeg", "*.png", "*.webp"),
                new FileChooser.ExtensionFilter("All Files", "*.*")
        );
        File file = chooser.showOpenDialog(null);
        if (file != null) {
            long maxBytes = 2L * 1024 * 1024; // 2 MB max
            if (file.length() > maxBytes) {
                showSaveStatus("Image size exceeds 2 MB limit.", true);
                return;
            }
            selectedAvatarFile = file;
            if (chosenFileLabel != null) {
                chosenFileLabel.setText(file.getName());
            }
            showSaveStatus("Selected: " + file.getName(), false);
        }
    }

    /** Save the username change and/or profile picture via API. */
    @FXML
    private void onSaveProfile() {
        User u = Session.currentUser();
        if (u == null) return;

        String newUsername = usernameField.getText().trim();
        if (newUsername.isBlank()) {
            showSaveStatus("Username cannot be empty.", true);
            return;
        }

        String token = Session.authToken();
        if (token == null) return;

        showSaveStatus("Saving…", false);
        new Thread(() -> {
            try {
                forum.api.dto.UserDto updated = api.updateProfile(token, newUsername, selectedAvatarFile);
                // Update local session so navbar reflects new name immediately
                u.setUsername(newUsername);
                if (updated != null && updated.avatar != null) {
                    u.setAvatar(updated.avatar);
                }
                selectedAvatarFile = null;
                Platform.runLater(() -> {
                    userNameLabel.setText(newUsername);
                    avatarLabel.setText(initial(newUsername));
                    if (profileAvatarPreview != null) profileAvatarPreview.setText(initial(newUsername));
                    if (chosenFileLabel != null) chosenFileLabel.setText("No file chosen");
                    showSaveStatus("Saved successfully.", false);
                    scheduleHideStatus(saveStatusLabel);
                });
            } catch (Exception e) {
                if (e instanceof InterruptedException) Thread.currentThread().interrupt();
                Platform.runLater(() -> showSaveStatus("Failed: " + e.getMessage(), true));
            }
        }, "save-profile").start();
    }

    /** Save the password change via API. */
    @FXML
    private void onSavePassword() {
        String current = currentPasswordField.getText();
        String next    = newPasswordField.getText();
        String confirm = confirmPasswordField.getText();

        if (current.isBlank() || next.isBlank()) {
            showPasswordStatus("All password fields are required.", true);
            return;
        }
        if (!next.equals(confirm)) {
            showPasswordStatus("New passwords do not match.", true);
            return;
        }
        if (next.length() < 8) {
            showPasswordStatus("Password must be at least 8 characters.", true);
            return;
        }

        String token = Session.authToken();
        if (token == null) return;

        showPasswordStatus("Saving…", false);
        new Thread(() -> {
            try {
                api.updatePassword(token, current, next, confirm);
                Platform.runLater(() -> {
                    currentPasswordField.clear();
                    newPasswordField.clear();
                    confirmPasswordField.clear();
                    showPasswordStatus("Password updated successfully.", false);
                    scheduleHideStatus(passwordStatusLabel);
                });
            } catch (Exception e) {
                if (e instanceof InterruptedException) Thread.currentThread().interrupt();
                Platform.runLater(() -> showPasswordStatus("Failed: " + e.getMessage(), true));
            }
        }, "save-password").start();
    }

    /**
     * DELETE /api/profile — confirm and delete account.
     */
    @FXML
    private void onDeleteAccount() {
        Alert confirm = new Alert(Alert.AlertType.CONFIRMATION);
        confirm.setTitle("Delete Account");
        confirm.setHeaderText("Are you sure you want to delete your account?");
        confirm.setContentText("Once your account is deleted, all of its resources and data will be permanently deleted. Before deleting your account, please download any data you wish to retain.");
        if (getClass().getResource("/forum/css/aces.css") != null) {
            confirm.getDialogPane().getStylesheets().add(getClass().getResource("/forum/css/aces.css").toExternalForm());
        }
        confirm.getDialogPane().getStyleClass().add("custom-alert");
        confirm.getDialogPane().setPrefWidth(540);
        confirm.getDialogPane().setMinHeight(javafx.scene.layout.Region.USE_PREF_SIZE);
        confirm.setGraphic(null);

        javafx.scene.control.Button okButton = (javafx.scene.control.Button) confirm.getDialogPane().lookupButton(ButtonType.OK);
        if (okButton != null) {
            okButton.setText("Delete Account");
            okButton.getStyleClass().addAll("btn", "btn-danger");
        }
        javafx.scene.control.Button cancelButton = (javafx.scene.control.Button) confirm.getDialogPane().lookupButton(ButtonType.CANCEL);
        if (cancelButton != null) {
            cancelButton.setText("Cancel");
            cancelButton.getStyleClass().addAll("btn", "btn-outline");
        }

        Optional<ButtonType> result = confirm.showAndWait();
        if (result.isPresent() && result.get() == ButtonType.OK) {
            String token = Session.authToken();
            Session.end();
            SceneManager.clearCache();
            new Thread(() -> {
                try {
                    if (token != null) api.deleteAccount(token);
                } catch (Exception ignore) {
                }
            }, "delete-account").start();
            SceneManager.show("Login", "ACES");
        }
    }

    // ── Navigation ───────────────────────────────────────────────────────────
    @FXML private void onDashboard() {
        User u = Session.currentUser();
        if (u != null) SceneManager.showHomeFor(u.getRole());
    }
    @FXML private void onGroups()     { SceneManager.goGroups(); }
    @FXML private void onMyProgress() { SceneManager.goStudentAssessment(); }
    @FXML private void onNewTopic()   { SceneManager.goTopicCreation(); }
    @FXML private void onQuizCenter() { SceneManager.goQuizManagement(); }
    @FXML private void onGrading()    { SceneManager.goParticipationGrading(); }
    @FXML private void onProfile()    { /* Already here */ }

    @FXML
    private void onLogout() {
        String token = Session.authToken();
        Session.end();
        SceneManager.clearCache();
        new Thread(() -> new AuthService().logout(token), "logout").start();
        SceneManager.show("Login", "ACES");
    }

    // ── Helpers ──────────────────────────────────────────────────────────────
    private void showSaveStatus(String msg, boolean isError) {
        if (saveStatusLabel == null) return;
        saveStatusLabel.setText(msg);
        saveStatusLabel.setStyle(isError ? "-fx-text-fill: #dc2626;" : "-fx-text-fill: #16a34a;");
        saveStatusLabel.setManaged(true);
        saveStatusLabel.setVisible(true);
    }

    private void showPasswordStatus(String msg, boolean isError) {
        if (passwordStatusLabel == null) return;
        passwordStatusLabel.setText(msg);
        passwordStatusLabel.setStyle(isError ? "-fx-text-fill: #dc2626;" : "-fx-text-fill: #16a34a;");
        passwordStatusLabel.setManaged(true);
        passwordStatusLabel.setVisible(true);
    }

    /** Auto-hide a success status label after 3 seconds. */
    private void scheduleHideStatus(Label label) {
        Thread t = new Thread(() -> {
            try {
                Thread.sleep(3000);
                Platform.runLater(() -> {
                    label.setManaged(false);
                    label.setVisible(false);
                });
            } catch (InterruptedException ignored) {
                Thread.currentThread().interrupt();
            }
        }, "hide-status");
        t.setDaemon(true);
        t.start();
    }

    /** Single first-letter initial — matches web x-avatar component. */
    private String initial(String name) {
        if (name == null || name.isBlank()) return "?";
        return String.valueOf(name.trim().charAt(0)).toUpperCase();
    }
}
