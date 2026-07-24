package forum.controllers;

import forum.config.AppConstants;
import javafx.event.ActionEvent;
import javafx.fxml.FXML;
import javafx.scene.Node;
import javafx.scene.control.Hyperlink;
import javafx.stage.Stage;
import java.awt.Desktop;
import java.net.URI;

public class SupportController {

    @FXML private Hyperlink supportEmailLink;

    @FXML
    public void initialize() {
        if (supportEmailLink != null) {
            supportEmailLink.setText(AppConstants.SUPPORT_EMAIL);
        }
    }

    @FXML
    public void onEmailClick(ActionEvent event) {
        try {
            Desktop.getDesktop().mail(new URI("mailto:" + AppConstants.SUPPORT_EMAIL));
        } catch (Exception e) {
            e.printStackTrace();
        }
    }

    @FXML
    public void onClose(ActionEvent event) {
        Stage stage = (Stage) ((Node) event.getSource()).getScene().getWindow();
        stage.close();
    }
}
