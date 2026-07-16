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
import javafx.scene.control.ComboBox;
import javafx.scene.control.Label;

/** Controller for Register.fxml — creates a local account then routes to the role home. */
public class RegisterController {

    @FXML private IconTextField fullNameField;
    @FXML private IconTextField emailField;
    @FXML private RevealPasswordField passwordField;
    @FXML private RevealPasswordField confirmField;
    @FXML private ComboBox<String> roleComboBox;
    @FXML private CheckBox agreeTerms;
    @FXML private Label errorLabel;

    private final AuthService authService = new AuthService();

    @FXML
    private void initialize() {
        if (errorLabel != null) errorLabel.setManaged(false);
        if (roleComboBox != null) {
            roleComboBox.getItems().addAll("Student", "Lecturer", "Admin");
            roleComboBox.setValue("Student");
        }
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

    @FXML
    private void onTerms() {
        try {
            javafx.fxml.FXMLLoader loader = new javafx.fxml.FXMLLoader(getClass().getResource("/forum/fxml/TermsOfService.fxml"));
            javafx.scene.Parent root = loader.load();
            javafx.stage.Stage stage = new javafx.stage.Stage();
            stage.initModality(javafx.stage.Modality.APPLICATION_MODAL);
            stage.initStyle(javafx.stage.StageStyle.UNDECORATED);
            
            javafx.scene.Scene scene = new javafx.scene.Scene(root);
            stage.setScene(scene);
            stage.centerOnScreen();
            stage.showAndWait();
        } catch (java.io.IOException e) {
            e.printStackTrace();
        }
    }

    private String text(IconTextField f) { return f == null ? "" : f.getText(); }

    private Role selectedRole() {
        if (roleComboBox != null && roleComboBox.getValue() != null) {
            return Role.fromLabel(roleComboBox.getValue());
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
