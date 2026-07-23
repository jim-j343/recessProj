package forum.controllers;

import forum.api.ApiClient;
import forum.api.ApiException;
import forum.api.dto.GroupDto;
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

public class GroupEditController {

    @FXML private Label     avatarLabel;
    @FXML private Label     userNameLabel;
    @FXML private TextField nameField;
    @FXML private TextField courseField;
    @FXML private TextArea  descField;
    @FXML private TextField warnDaysField;
    @FXML private TextField blkDaysField;
    @FXML private Button    submitBtn;
    @FXML private Label     statusLabel;

    @FXML private Label      navMyProgress;
    @FXML private Label      navNewTopic;
    @FXML private Label      navQuizCenter;
    @FXML private Label      navGrading;
    @FXML private Label      navMembers;
    @FXML private Label      navAnalytics;
    @FXML private Label      navModeration;

    @FXML private javafx.scene.control.MenuButton notifButton;
    @FXML private Label notifBadge;

    private final ApiClient api = new ApiClient();
    private GroupDto group;

    public void setGroup(GroupDto group) {
        this.group = group;
        nameField.setText(group.name);
        courseField.setText(group.courseName);
        descField.setText(group.description);
        warnDaysField.setText(String.valueOf(group.warningDays > 0 ? group.warningDays : 7));
        blkDaysField.setText(String.valueOf(group.blacklistDays > 0 ? group.blacklistDays : 30));
    }

    @FXML
    private void initialize() {
        User u = Session.currentUser();
        if (u != null) {
            avatarLabel.setText(initial(u.displayName()));
            userNameLabel.setText(u.displayName());
            if (u.getRole() == forum.models.Role.STUDENT && navMyProgress != null) {
                navMyProgress.setManaged(true); navMyProgress.setVisible(true);
            }
            if (u.getRole() != forum.models.Role.SYSTEM_ADMIN && navNewTopic != null) {
                navNewTopic.setManaged(true); navNewTopic.setVisible(true);
            }
            if (u.getRole() == forum.models.Role.LECTURER && navQuizCenter != null && navGrading != null) {
                navQuizCenter.setManaged(true); navQuizCenter.setVisible(true);
                navGrading.setManaged(true); navGrading.setVisible(true);
            }
            if (u.getRole() == forum.models.Role.SYSTEM_ADMIN && navMembers != null) {
                navMembers.setManaged(true); navMembers.setVisible(true);
                navAnalytics.setManaged(true); navAnalytics.setVisible(true);
                navModeration.setManaged(true); navModeration.setVisible(true);
            }
        }
        if (notifButton != null) {
            NavbarHelper.loadNotifications(api, notifButton, notifBadge);
        }
    }

    @FXML
    private void onSubmit() {
        if (group == null) return;
        String name = nameField.getText().trim();
        String course = courseField.getText().trim();
        String warnStr = warnDaysField.getText().trim();
        String blkStr = blkDaysField.getText().trim();

        if (name.isBlank() || course.isBlank() || warnStr.isBlank() || blkStr.isBlank()) {
            showStatus("Please fill in all required fields.", true);
            return;
        }

        int warnDays;
        int blkDays;
        try {
            warnDays = Integer.parseInt(warnStr);
            blkDays = Integer.parseInt(blkStr);
        } catch (NumberFormatException e) {
            showStatus("Warning and blacklist days must be valid numbers.", true);
            return;
        }

        String token = Session.authToken();
        if (token == null) {
            showStatus("You must be logged in to edit a group.", true);
            return;
        }

        submitBtn.setDisable(true);
        submitBtn.setText("Saving…");
        showStatus("", false);

        String desc = descField.getText().trim();

        Thread worker = new Thread(() -> {
            try {
                api.updateGroup(token, group.groupId, name, course, desc, warnDays, blkDays);
                Platform.runLater(() -> SceneManager.showGroup(group.groupId));
            } catch (ApiException e) {
                Platform.runLater(() -> {
                    showStatus("Failed: " + e.getMessage(), true);
                    submitBtn.setDisable(false);
                    submitBtn.setText("Save Changes");
                });
            } catch (Exception e) {
                if (e instanceof InterruptedException) Thread.currentThread().interrupt();
                Platform.runLater(() -> {
                    showStatus("Error: " + e.getMessage(), true);
                    submitBtn.setDisable(false);
                    submitBtn.setText("Save Changes");
                });
            }
        }, "edit-group");
        worker.setDaemon(true);
        worker.start();
    }

    @FXML private void onCancel()    { if (group != null) SceneManager.showGroup(group.groupId); else SceneManager.goGroups(); }
    @FXML private void onGroups()    { SceneManager.goGroups(); }
    @FXML private void onDashboard() {
        User u = Session.currentUser();
        if (u != null) SceneManager.showHomeFor(u.getRole());
    }
    @FXML private void onNewTopic()  { SceneManager.goTopicCreation(); }
    @FXML private void onQuizCenter(){ SceneManager.goQuizManagement(); }
    @FXML private void onGrading()   { SceneManager.goParticipationGrading(); }
    @FXML private void onMembers()   { SceneManager.goAdminMembers(); }
    @FXML private void onAnalytics() { SceneManager.goAdminAnalytics(); }
    @FXML private void onMyProgress(){ SceneManager.goStudentAssessment(); }
    @FXML private void onModeration() { SceneManager.goAdminModeration(); }
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

    private String initial(String name) {
        if (name == null || name.isBlank()) return "?";
        return String.valueOf(name.trim().charAt(0)).toUpperCase();
    }
}
