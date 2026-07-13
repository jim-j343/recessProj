package forum.controllers;

import forum.app.SceneManager;
import forum.app.Session;
import forum.models.User;
import forum.services.AuthService;
import javafx.fxml.FXML;
import javafx.scene.control.Label;

public class ProfileController {

    @FXML private Label avatarLabel;
    @FXML private Label userNameLabel;

    @FXML
    private void initialize() {
        User u = Session.currentUser();
        if (u != null) {
            String initials = u.displayName().length() >= 2
                    ? u.displayName().substring(0, 2).toUpperCase()
                    : u.displayName().toUpperCase();
            if (avatarLabel != null) avatarLabel.setText(initials);
            if (userNameLabel != null) userNameLabel.setText(u.displayName());
        }
    }

    @FXML
    private void onDashboard() {
        User u = Session.currentUser();
        if (u != null) {
            SceneManager.showHomeFor(u.getRole());
        }
    }

    @FXML private void onGroups()    { SceneManager.goGroups(); }
    @FXML private void onMembers()   { SceneManager.goAdminMembers(); }
    @FXML private void onAnalytics() { SceneManager.goAdminAnalytics(); }
    @FXML private void onProfile()   { /* Already here */ }

    @FXML
    private void onLogout() {
        String token = Session.authToken();
        Session.end();
        new Thread(() -> new AuthService().logout(token), "logout").start();
        SceneManager.show("Login", "Smart Discussion Forum");
    }
}
