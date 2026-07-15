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
    if (emailField != null) emailField.setDisable(true);
    if (passwordField != null) passwordField.setDisable(false);

    String email    = email();
    String password = password();
    Role   role     = selectedRole();

    Thread worker = new Thread(() -> {
        try {
            System.out.println("[LOGIN] Attempting login for: " + email);
            AuthService.Result result = authService.login(email, password, role);
            System.out.println("[LOGIN] Success — user: " + result.user().displayName()
                    + " role: " + result.user().getRole()
                    + " token: " + result.token());
            javafx.application.Platform.runLater(() -> {
                Session.begin(result.user(), result.token());
                SceneManager.showHomeFor(result.user().getRole());
            });
        } catch (AuthService.AuthException e) {
            System.out.println("[LOGIN] AuthException: " + e.getMessage());
            javafx.application.Platform.runLater(() -> {
                showError(e.getMessage());
                if (emailField != null) emailField.setDisable(false);
                if (passwordField != null) passwordField.setDisable(false);
            });
        } catch (Exception e) {
            System.out.println("[LOGIN] Unexpected exception: " + e.getMessage());
            e.printStackTrace();
            javafx.application.Platform.runLater(() -> {
                showError("Unexpected error: " + e.getMessage());
                if (emailField != null) emailField.setDisable(false);
                if (passwordField != null) passwordField.setDisable(false);
            });
        }
    }, "aces-login");
    worker.setDaemon(true);
    worker.start();
}

    @FXML
    private void onSignUp() {
        SceneManager.show("Register", "ACES — Create Account");
    }

    @FXML
    private void onForgotPassword() {
        SceneManager.show("ForgotPassword", "ACES — Forgot Password");
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
