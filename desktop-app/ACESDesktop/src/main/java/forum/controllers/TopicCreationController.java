package forum.controllers;

import forum.app.SceneManager;
import forum.app.Session;
import forum.app.ViewState;
import forum.database.TopicDao;
import forum.models.Topic;
import forum.models.User;

import javafx.fxml.FXML;
import javafx.scene.control.ComboBox;
import javafx.scene.control.Label;
import javafx.scene.control.TextArea;
import javafx.scene.control.TextField;

/** Publishes a new topic into the local cache (queued for sync). */
public class TopicCreationController {

    private static final long DEFAULT_GROUP_ID = 1;

    @FXML private TextField titleField;
    @FXML private ComboBox<String> categoryCombo;
    @FXML private TextArea descriptionArea;
    @FXML private Label errorLabel;

    private final TopicDao topicDao = new TopicDao();

    @FXML
    private void initialize() {
        if (errorLabel != null) errorLabel.setManaged(false);
        if (categoryCombo != null && categoryCombo.getItems().isEmpty()) {
            categoryCombo.getItems().addAll("Core Curriculum", "Elective", "Seminar", "General");
        }
    }

    @FXML
    private void onPublish() {
        String title = titleField == null ? "" : titleField.getText();
        if (title == null || title.isBlank()) { showError("Please enter a topic title."); return; }
        String category = (categoryCombo != null && categoryCombo.getValue() != null)
                ? categoryCombo.getValue() : "General";
        User u = Session.currentUser();
        long creatorId = (u != null) ? u.getUserId() : 0;

        Topic created = topicDao.create(DEFAULT_GROUP_ID, creatorId, title.trim(), category);
        if (created == null) { showError("Could not publish the topic."); return; }
        ViewState.setSelectedTopic(created);
        SceneManager.show("TopicDetail", "ACES — " + created.getTitle());
    }

    @FXML
    private void onCancel() {
        SceneManager.show("ForumDashboard", "ACES");
    }

    private void showError(String msg) {
        if (errorLabel == null) return;
        errorLabel.setText(msg);
        errorLabel.setManaged(true);
        errorLabel.setVisible(true);
    }
}
