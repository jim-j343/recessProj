package forum.controllers;

import forum.api.ApiClient;
import forum.api.ApiException;
import forum.app.SceneManager;
import forum.app.Session;
import forum.models.User;
import forum.util.NavbarHelper;

import javafx.application.Platform;
import javafx.fxml.FXML;
import javafx.scene.control.Button;
import javafx.scene.control.Label;
import javafx.scene.control.TextArea;
import javafx.scene.control.TextField;

public class GroupCreateController {

    @FXML private Label     avatarLabel;
    @FXML private Label     userNameLabel;
    @FXML private TextField nameField;
    @FXML private TextArea  descField;
    @FXML private TextField warnDaysField;
    @FXML private TextField blkDaysField;
    @FXML private Button    submitBtn;
    @FXML private Label     statusLabel;

    private final ApiClient api = new ApiClient();

    @FXML
    private void initialize() {
        User u = Session.currentUser();
        if (u != null) {
            avatarLabel.setText(initial(u.displayName()));
            userNameLabel.setText(u.displayName());
        }
    }

    @FXML
    private void onSubmit() {
        String name = nameField.getText().trim();
        if (name.isBlank()) {
            showStatus("Group name is required.", true);
            return;
        }

        String token = Session.authToken();
        if (token == null) {
            showStatus("You must be logged in to create a group.", true);
            return;
        }

        submitBtn.setDisable(true);
        submitBtn.setText("Creating…");
        showStatus("", false);

        String desc = descField.getText().trim();

        Thread worker = new Thread(() -> {
            try {
                api.createGroup(token, name, desc);
                Platform.runLater(() -> SceneManager.goGroups());
            } catch (ApiException e) {
                Platform.runLater(() -> {
                    showStatus("Failed: " + e.getMessage(), true);
                    submitBtn.setDisable(false);
                    submitBtn.setText("Create Group");
                });
            } catch (Exception e) {
                if (e instanceof InterruptedException) Thread.currentThread().interrupt();
                Platform.runLater(() -> {
                    showStatus("Error: " + e.getMessage(), true);
                    submitBtn.setDisable(false);
                    submitBtn.setText("Create Group");
                });
            }
        }, "create-group");
        worker.setDaemon(true);
        worker.start();
    }

    @FXML private void onCancel()    { SceneManager.goGroups(); }
    @FXML private void onGroups()    { SceneManager.goGroups(); }
    @FXML private void onDashboard() {
        User u = Session.currentUser();
        if (u != null) SceneManager.showHomeFor(u.getRole());
    }
    @FXML private void onProfile()   { SceneManager.goProfile(); }
    @FXML private void onLogout()    {
        String token = Session.authToken();
        Session.end();
        new Thread(() -> new forum.services.AuthService().logout(token), "logout").start();
        SceneManager.show("Login", "ACES");
    }

    private void showStatus(String msg, boolean visible) {
        statusLabel.setText(msg);
        statusLabel.setManaged(visible);
        statusLabel.setVisible(visible);
    }

    /** Single first-letter initial — matches web x-avatar component. */
    private String initial(String name) {
        if (name == null || name.isBlank()) return "?";
        return String.valueOf(name.trim().charAt(0)).toUpperCase();
    }
}
