package forum.controllers;

import forum.api.ApiClient;
import forum.app.SceneManager;
import forum.app.Session;
import forum.models.User;
import forum.services.AuthService;
import forum.util.NavbarHelper;

import javafx.fxml.FXML;
import javafx.scene.control.Label;
import javafx.scene.control.MenuButton;

public class ProfileController {

    @FXML private Label      avatarLabel;
    @FXML private Label      userNameLabel;
    @FXML private MenuButton notifButton;
    @FXML private Label      notifBadge;

    private final ApiClient api = new ApiClient();

    @FXML
    private void initialize() {
        User u = Session.currentUser();
        if (u != null) {
            // Single first-letter initial — matches web x-avatar component
            if (avatarLabel != null)   avatarLabel.setText(initial(u.displayName()));
            if (userNameLabel != null) userNameLabel.setText(u.displayName());
        }
        // Load real notifications from backend
        NavbarHelper.loadNotifications(api, notifButton, notifBadge);
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

    /** Single first-letter initial — matches web x-avatar component. */
    private String initial(String name) {
        if (name == null || name.isBlank()) return "?";
        return String.valueOf(name.trim().charAt(0)).toUpperCase();
    }
}
