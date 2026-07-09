package forum.controllers;

import forum.app.SceneManager;
import forum.app.Session;
import forum.models.Role;
import forum.models.User;
import forum.services.AuthService;
import forum.ui.IconTextField;
import forum.ui.RevealPasswordField;

import javafx.fxml.FXML;
import javafx.scene.control.CheckBox;
import javafx.scene.control.Label;
import javafx.scene.control.ToggleButton;
import javafx.scene.control.ToggleGroup;

/** Controller for Login.fxml — first real vertical slice (auth + role routing). */
public class LoginController {

    @FXML private IconTextField emailField;
    @FXML private RevealPasswordField passwordField;
    @FXML private ToggleGroup role;
    @FXML private CheckBox rememberMe;
    @FXML private Label errorLabel;

    private final AuthService authService = new AuthService();

    @FXML
    private void initialize() {
        if (errorLabel != null) errorLabel.setManaged(false);
    }

    @FXML
    private void onSignIn() {
        clearError();
        try {
            User user = authService.login(email(), password(), selectedRole());
            Session.begin(user, null);              // token added once the API exists
            SceneManager.showHomeFor(user.getRole());
        } catch (AuthService.AuthException e) {
            showError(e.getMessage());
        }
    }

    @FXML
    private void onSignUp() {
        SceneManager.show("Register", "ACES — Create Account");
    }

    private String email() {
        return emailField == null ? "" : emailField.getText();
    }

    private String password() {
        return passwordField == null ? "" : passwordField.getText();
    }

    private Role selectedRole() {
        if (role != null && role.getSelectedToggle() instanceof ToggleButton tb) {
            return Role.fromLabel(tb.getText());
        }
        return Role.STUDENT;
    }

    private void showError(String message) {
        if (errorLabel == null) return;
        errorLabel.setText(message);
        errorLabel.setManaged(true);
        errorLabel.setVisible(true);
    }

    private void clearError() {
        if (errorLabel == null) return;
        errorLabel.setText("");
        errorLabel.setManaged(false);
        errorLabel.setVisible(false);
    }
}
