package forum.controllers;

import forum.api.ApiClient;
import forum.api.dto.PostDto;
import forum.api.dto.TopicDetailResponse;
import forum.app.SceneManager;
import forum.app.Session;
import forum.app.ViewState;
import forum.database.PostDao;
import forum.database.TopicDao;
import forum.models.Post;
import forum.models.Topic;
import forum.models.User;

import javafx.application.Platform;
import javafx.fxml.FXML;
import javafx.scene.control.Label;
import javafx.scene.control.TextField;
import javafx.scene.layout.VBox;

import java.util.List;

/**
 * Topic thread — offline-first. Shows cached posts immediately, then (if the
 * topic exists on the server) pulls the latest posts. Replies post to the API
 * when online, otherwise queue locally for the next sync. Network off the UI thread.
 */
public class TopicDetailController {

    @FXML private Label topicTitleLabel;
    @FXML private Label repliesCountLabel;
    @FXML private VBox postList;
    @FXML private TextField composerField;

    private final PostDao postDao = new PostDao();
    private final TopicDao topicDao = new TopicDao();
    private final ApiClient api = new ApiClient();

    private Topic topic;

    @FXML
    private void initialize() {
        topic = ViewState.getSelectedTopic();
        if (topic == null) { onBack(); return; }
        if (topicTitleLabel != null) topicTitleLabel.setText(topic.getTitle());
        renderPosts(postDao.listByTopic(topic.getTopicId()));   // instant, from cache
        fetchOnline();
    }

    /** If the topic is on the server, pull its latest posts off the UI thread. */
    private void fetchOnline() {
        String token = Session.authToken();
        long serverTopicId = topicDao.serverIdFor(topic.getTopicId());
        if (token == null || token.isBlank() || serverTopicId <= 0) return;

        Thread worker = new Thread(() -> {
            try {
                TopicDetailResponse detail = api.getTopic(token, serverTopicId);
                if (detail.topic != null) topicDao.upsertFromServer(detail.topic);
                if (detail.posts != null) for (PostDto p : detail.posts) postDao.upsertFromServer(p);
                List<Post> fresh = postDao.listByTopic(topic.getTopicId());
                Platform.runLater(() -> renderPosts(fresh));
            } catch (Exception ignored) {
                // stay on the cached view if the fetch fails
            }
        }, "aces-topic-fetch");
        worker.setDaemon(true);
        worker.start();
    }

    private void renderPosts(List<Post> posts) {
        if (postList == null) return;
        postList.getChildren().clear();
        if (repliesCountLabel != null) repliesCountLabel.setText(posts.size() + " Replies");
        if (posts.isEmpty()) {
            Label empty = new Label("No replies yet. Be the first to respond.");
            empty.getStyleClass().add("muted");
            postList.getChildren().add(empty);
            return;
        }
        for (Post p : posts) postList.getChildren().add(postCard(p));
    }

    private VBox postCard(Post p) {
        Label author = new Label(p.getAuthorName() == null ? "Unknown" : p.getAuthorName());
        author.getStyleClass().add("label-strong");
        Label body = new Label(p.getContent());
        body.getStyleClass().add("body");
        body.setWrapText(true);
        Label meta = new Label(p.isSynced() ? "synced" : "pending sync");
        meta.getStyleClass().add("muted");

        VBox card = new VBox(6, author, body, meta);
        card.getStyleClass().add("card");
        return card;
    }

    @FXML
    private void onReply() {
        if (composerField == null || topic == null) return;
        String content = composerField.getText();
        if (content == null || content.isBlank()) return;
        composerField.clear();

        User u = Session.currentUser();
        long authorId = (u != null) ? u.getUserId() : 0;
        String token = Session.authToken();
        long serverTopicId = topicDao.serverIdFor(topic.getTopicId());
        String body = content.trim();

        Thread worker = new Thread(() -> {
            // Online — post to the server and cache the result.
            if (token != null && !token.isBlank() && serverTopicId > 0) {
                try {
                    PostDto dto = api.createPost(token, serverTopicId, body, null);
                    postDao.upsertFromServer(dto);
                    List<Post> fresh = postDao.listByTopic(topic.getTopicId());
                    Platform.runLater(() -> renderPosts(fresh));
                    return;
                } catch (Exception offline) {
                    // fall through to the local queue
                }
            }
            // Offline — save locally and queue for sync.
            postDao.create(topic.getTopicId(), authorId, null, body);
            List<Post> fresh = postDao.listByTopic(topic.getTopicId());
            Platform.runLater(() -> renderPosts(fresh));
        }, "aces-reply");
        worker.setDaemon(true);
        worker.start();
    }

    @FXML
    private void onBack() {
        SceneManager.show("ForumDashboard", "ACES");
    }

    @FXML
    private void onExportPdf() {
        if (topic == null) return;
        String token = Session.authToken();
        long serverTopicId = topicDao.serverIdFor(topic.getTopicId());
        if (token == null || serverTopicId <= 0) return;

        // Open the PDF export URL in the system browser
        String url = forum.config.DatabaseConfig.API_BASE_URL
                .replace("/api", "") + "/topics/" + serverTopicId + "/export-pdf";
        try {
            java.awt.Desktop.getDesktop().browse(java.net.URI.create(url));
        } catch (Exception e) {
            e.printStackTrace();
        }
    }

    @FXML
    private void onShare() {
        if (topic == null) return;
        // Build the web URL for this topic and copy it to clipboard
        String url = forum.config.DatabaseConfig.API_BASE_URL
                .replace("/api", "") + "/topics/" + topicDao.serverIdFor(topic.getTopicId());

        javafx.scene.input.ClipboardContent content = new javafx.scene.input.ClipboardContent();
        content.putString(url);
        javafx.scene.input.Clipboard.getSystemClipboard().setContent(content);

        // Brief visual feedback
        if (topicTitleLabel != null) {
            String original = topicTitleLabel.getText();
            topicTitleLabel.setText("✓ Link copied to clipboard!");
            new Thread(() -> {
                try { Thread.sleep(2000); } catch (InterruptedException ignored) {}
                Platform.runLater(() -> topicTitleLabel.setText(original));
            }).start();
        }
    }
}
