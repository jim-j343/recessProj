package forum.controllers;

import javafx.event.ActionEvent;
import javafx.fxml.FXML;
import javafx.scene.control.Alert;
import javafx.scene.control.Alert.AlertType;

public class FooterController {

    @FXML
    public void onPrivacyPolicy(ActionEvent event) {
        showDialog("/forum/fxml/PrivacyPolicy.fxml");
    }

    @FXML
    public void onPlatformRules(ActionEvent event) {
        showDialog("/forum/fxml/TermsOfService.fxml");
    }

    @FXML
    public void onSupport(ActionEvent event) {
        showDialog("/forum/fxml/Support.fxml");
    }

    private void showDialog(String resourcePath) {
        try {
            javafx.fxml.FXMLLoader loader = new javafx.fxml.FXMLLoader(getClass().getResource(resourcePath));
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
}
