package forum.controllers;

import forum.app.SceneManager;
import forum.app.Session;
import forum.models.User;
import forum.util.NavbarHelper;
import forum.api.ApiClient;
import javafx.fxml.FXML;
import javafx.scene.control.Label;
import javafx.scene.control.MenuButton;

public class StudentAssessmentController {

    @FXML private Label      avatarLabel;
    @FXML private Label      userNameLabel;
    @FXML private MenuButton notifButton;
    @FXML private Label      notifBadge;

    private final ApiClient api = new ApiClient();

    @FXML
    private void initialize() {
        User u = Session.currentUser();
        if (u != null) {
            if (avatarLabel != null)
                avatarLabel.setText(String.valueOf(u.displayName().charAt(0)).toUpperCase());
            if (userNameLabel != null)
                userNameLabel.setText(u.displayName());
        }
        NavbarHelper.loadNotifications(api, notifButton, notifBadge);
    }

    @FXML private void onDashboard() {
        User u = Session.currentUser();
        if (u != null) SceneManager.showHomeFor(u.getRole());
    }
    @FXML private void onGroups()  { SceneManager.goGroups(); }
    @FXML private void onForum()   { SceneManager.goForumDashboard(); }
    @FXML private void onProfile() { SceneManager.goProfile(); }
    @FXML private void onLogout()  {
        String token = Session.authToken();
        Session.end();
        new Thread(() -> new forum.services.AuthService().logout(token), "logout").start();
        SceneManager.show("Login", "ACES");
    }
}