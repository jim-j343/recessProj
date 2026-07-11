package forum.controllers;

import forum.app.SceneManager;
import forum.app.Session;
import forum.app.ViewState;
import forum.database.TopicDao;
import forum.models.Topic;
import forum.models.User;
import forum.services.AuthService;
import forum.services.SyncService;

import javafx.application.Platform;
import javafx.fxml.FXML;
import javafx.geometry.Insets;
import javafx.scene.control.Label;
import javafx.scene.layout.Region;
import javafx.scene.layout.VBox;

import java.util.List;

/**
 * Forum Dashboard — offline-first.
 * Renders topics from the local cache immediately, then runs a background
 * sync (push queued rows + pull server topics) and re-renders.
 */
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
        renderTopics(topicDao.listRecent(10));   // instant, from cache
        syncInBackground();                       // push/pull, then refresh
    }

    /** Push pending rows + pull server topics off the UI thread, then re-render. */
    private void syncInBackground() {
        Thread worker = new Thread(() -> {
            new SyncService().syncNow();
            List<Topic> fresh = topicDao.listRecent(10);
            Platform.runLater(() -> renderTopics(fresh));
        }, "aces-forum-sync");
        worker.setDaemon(true);
        worker.start();
    }

    private void renderTopics(List<Topic> topics) {
        if (discussionList == null) return;
        discussionList.getChildren().clear();
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
        SceneManager.show("TopicDetail", "Smart Discussion Forum — " + t.getTitle());
    }

    @FXML
    private void onNewThread() {
        SceneManager.show("TopicCreation", "Smart Discussion Forum — New Topic");
    }

    @FXML
    private void onLogout() {
        String token = Session.authToken();
        Session.end();
        // best-effort server-side revoke, off the UI thread
        Thread t = new Thread(() -> new AuthService().logout(token), "aces-logout");
        t.setDaemon(true);
        t.start();
        SceneManager.show("Login", "Smart Discussion Forum");
    }

    private String safe(String s) { return s == null ? "Unknown" : s; }
}
