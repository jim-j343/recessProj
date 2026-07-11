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

/** Controller for Register.fxml — creates a local account then routes to the role home. */
public class RegisterController {

    @FXML private IconTextField fullNameField;
    @FXML private IconTextField emailField;
    @FXML private RevealPasswordField passwordField;
    @FXML private RevealPasswordField confirmField;
    @FXML private ToggleGroup role;
    @FXML private CheckBox agreeTerms;
    @FXML private Label errorLabel;

    private final AuthService authService = new AuthService();

    @FXML
    private void initialize() {
        if (errorLabel != null) errorLabel.setManaged(false);
    }

    @FXML
    private void onRegister() {
        clearError();
        String name = text(fullNameField);
        String email = text(emailField);
        String pwd = passwordField == null ? "" : passwordField.getText();
        String confirm = confirmField == null ? "" : confirmField.getText();

        if (name.isBlank())            { showError("Please enter your full name."); return; }
        if (!pwd.equals(confirm))      { showError("Passwords do not match."); return; }
        if (agreeTerms != null && !agreeTerms.isSelected()) {
            showError("Please agree to the Platform Rules and Terms of Service.");
            return;
        }
        try {
            AuthService.Result result = authService.register(name, email, pwd, selectedRole());
            if (result.user() == null) { showError("Could not create the account."); return; }
            Session.begin(result.user(), result.token());
            SceneManager.showHomeFor(result.user().getRole());
        } catch (AuthService.AuthException e) {
            showError(e.getMessage());
        }
    }

    @FXML
    private void onSignIn() {
        SceneManager.show("Login", "ACES");
    }

    private String text(IconTextField f) { return f == null ? "" : f.getText(); }

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
