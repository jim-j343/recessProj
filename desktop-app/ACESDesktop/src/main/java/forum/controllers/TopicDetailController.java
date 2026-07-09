package forum.controllers;

import forum.app.SceneManager;
import forum.app.Session;
import forum.app.ViewState;
import forum.database.PostDao;
import forum.models.Post;
import forum.models.Topic;
import forum.models.User;

import javafx.fxml.FXML;
import javafx.geometry.Insets;
import javafx.scene.control.Label;
import javafx.scene.control.TextField;
import javafx.scene.layout.VBox;

import java.util.List;

/** Shows a topic's posts from the local cache and adds replies (persisted + queued). */
public class TopicDetailController {

    @FXML private Label topicTitleLabel;
    @FXML private Label repliesCountLabel;
    @FXML private VBox postList;
    @FXML private TextField composerField;

    private final PostDao postDao = new PostDao();
    private Topic topic;

    @FXML
    private void initialize() {
        topic = ViewState.getSelectedTopic();
        if (topic == null) { onBack(); return; }
        if (topicTitleLabel != null) topicTitleLabel.setText(topic.getTitle());
        reload();
    }

    private void reload() {
        if (postList == null || topic == null) return;
        postList.getChildren().clear();
        List<Post> posts = postDao.listByTopic(topic.getTopicId());
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
        User u = Session.currentUser();
        long authorId = (u != null) ? u.getUserId() : 0;
        postDao.create(topic.getTopicId(), authorId, null, content.trim());
        composerField.clear();
        reload();
    }

    @FXML
    private void onBack() {
        SceneManager.show("ForumDashboard", "ACES");
    }
}
