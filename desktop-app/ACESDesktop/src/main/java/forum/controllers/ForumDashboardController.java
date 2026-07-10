package forum.controllers;

import forum.app.SceneManager;
import forum.app.Session;
import forum.app.ViewState;
import forum.database.TopicDao;
import forum.models.Topic;
import forum.models.User;

import javafx.fxml.FXML;
import javafx.geometry.Insets;
import javafx.scene.control.Label;
import javafx.scene.layout.HBox;
import javafx.scene.layout.Priority;
import javafx.scene.layout.Region;
import javafx.scene.layout.VBox;

import java.util.List;

/** Loads recent topics from the local cache into the Forum Dashboard. */
public class ForumDashboardController {

    @FXML private VBox discussionList;
    @FXML private Label userNameLabel;
    @FXML private Label userMetaLabel;

    private final TopicDao topicDao = new TopicDao();

    @FXML
    private void initialize() {
        User u = Session.currentUser();
        if (u != null) {
            if (userNameLabel != null) userNameLabel.setText(u.displayName());
            if (userMetaLabel != null && u.getRole() != null)
                userMetaLabel.setText(u.getRole().label());
        }
        reload();
    }

    private void reload() {
        if (discussionList == null) return;
        discussionList.getChildren().clear();
        List<Topic> topics = topicDao.listRecent(10);
        if (topics.isEmpty()) {
            Label empty = new Label("No discussions yet. Start the first thread.");
            empty.getStyleClass().add("muted");
            discussionList.getChildren().add(empty);
            return;
        }
        boolean first = true;
        for (Topic t : topics) {
            if (!first) {
                Region divider = new Region();
                divider.getStyleClass().add("divider");
                divider.setMinHeight(1);
                VBox.setMargin(divider, new Insets(12, 0, 12, 0));
                discussionList.getChildren().add(divider);
            }
            discussionList.getChildren().add(row(t));
            first = false;
        }
    }

    private VBox row(Topic t) {
        Label title = new Label(t.getTitle());
        title.getStyleClass().add("h-sm");
        title.setWrapText(true);

        String meta = "by " + safe(t.getAuthorName())
                + "  ·  " + t.getReplyCount() + " replies"
                + (t.getCategory() != null && !t.getCategory().isBlank() ? "  ·  " + t.getCategory() : "");
        Label metaLabel = new Label(meta);
        metaLabel.getStyleClass().add("muted");

        VBox box = new VBox(6, title, metaLabel);
        box.setPadding(new Insets(6, 0, 6, 0));
        box.setStyle("-fx-cursor: hand;");
        box.setOnMouseClicked(e -> openTopic(t));
        return box;
    }

    private void openTopic(Topic t) {
        ViewState.setSelectedTopic(t);
        SceneManager.show("TopicDetail", "ACES — " + t.getTitle());
    }

    @FXML
    private void onNewThread() {
        SceneManager.show("TopicCreation", "ACES — New Topic");
    }

    private String safe(String s) { return s == null ? "Unknown" : s; }
}
