package forum.controllers;

import forum.api.ApiClient;
import forum.api.ApiException;
import forum.api.dto.PostDto;
import forum.api.dto.MemberDto;
import forum.api.dto.TopicDetailResponse;
import forum.app.SceneManager;
import forum.app.Session;
import forum.app.ViewState;
import forum.database.PostDao;
import forum.database.TopicDao;
import forum.models.Post;
import forum.models.Role;
import forum.models.Topic;
import forum.models.User;

import javafx.application.Platform;
import javafx.fxml.FXML;
import javafx.geometry.Insets;
import javafx.scene.control.*;
import javafx.scene.layout.HBox;
import javafx.scene.layout.Region;
import javafx.scene.layout.VBox;

import java.util.List;
import java.util.Optional;

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
    @FXML private Button editTopicBtn;
    @FXML private Button deleteTopicBtn;
    @FXML private VBox excludePickerBox;
    @FXML private VBox excludeCheckboxList;
    @FXML private Button excludeToggleBtn;
    @FXML private Label excludeCountBadge;

    private final java.util.Set<Long> excludedUserIds = new java.util.HashSet<>();
    private final java.util.Map<Long, List<String>> excludedUsernamesByPost = new java.util.HashMap<>();

    private final PostDao postDao = new PostDao();
    private final TopicDao topicDao = new TopicDao();
    private final ApiClient api = new ApiClient();

    private Topic topic;
    private long currentUserId = -1;
    private boolean isAdmin = false;

    @FXML
    private void initialize() {
        topic = ViewState.getSelectedTopic();
        if (topic == null) { onBack(); return; }
        if (topicTitleLabel != null) topicTitleLabel.setText(topic.getTitle());

        User u = Session.currentUser();
        if (u != null) {
            currentUserId = u.getUserId();
            isAdmin = u.getRole() == Role.SYSTEM_ADMIN;
        }

        boolean canManageTopic = currentUserId == topic.getCreatorId() || isAdmin;
        if (editTopicBtn != null) { editTopicBtn.setManaged(canManageTopic); editTopicBtn.setVisible(canManageTopic); }
        if (deleteTopicBtn != null) { deleteTopicBtn.setManaged(canManageTopic); deleteTopicBtn.setVisible(canManageTopic); }

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
                if (detail.posts != null) {
                    for (PostDto p : detail.posts) {
                        postDao.upsertFromServer(p);
                        if (p.excluded_usernames != null) excludedUsernamesByPost.put(p.post_id, p.excluded_usernames);
                    }
                }
                List<Post> fresh = postDao.listByTopic(topic.getTopicId());
                Platform.runLater(() -> {
                    renderPosts(fresh);
                    if (detail.groupMembers != null) populateExcludePicker(detail.groupMembers);
                });
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

        boolean isOwn = p.getAuthorId() == currentUserId;
        List<String> hiddenFrom = excludedUsernamesByPost.get(p.getPostId());
        if (isOwn && hiddenFrom != null && !hiddenFrom.isEmpty()) {
            Label hidden = new Label("🔒 Hidden from " + String.join(", ", hiddenFrom));
            hidden.getStyleClass().add("subtle");
            hidden.setStyle("-fx-text-fill:#b45309; -fx-font-size:11px;");
            card.getChildren().add(hidden);
        }
        if (p.isSynced()) {
            HBox actions = new HBox(12);
            actions.setPadding(new Insets(4, 0, 0, 0));

            if (!isOwn) {
                Hyperlink flag = new Hyperlink("🚩 Report");
                flag.getStyleClass().add("subtle");
                flag.setOnAction(e -> onFlagPost(p));
                actions.getChildren().add(flag);
            }
            if (isOwn || isAdmin) {
                Hyperlink edit = new Hyperlink("✎ Edit");
                edit.getStyleClass().add("subtle");
                edit.setOnAction(e -> onEditPost(p));

                Hyperlink delete = new Hyperlink("Delete");
                delete.setStyle("-fx-text-fill:#ef4444;");
                delete.setOnAction(e -> onDeletePost(p));

                actions.getChildren().addAll(edit, delete);
            }
            if (!actions.getChildren().isEmpty()) card.getChildren().add(actions);
        }

        return card;
    }

    /** Builds the "hide this reply from" checkbox list from the topic's group roster. */
    private void populateExcludePicker(List<MemberDto> members) {
        if (excludeCheckboxList == null) return;
        excludeCheckboxList.getChildren().clear();
        excludedUserIds.clear();
        updateExcludeBadge();

        if (members.isEmpty()) {
            Label none = new Label("No other members in this group yet.");
            none.getStyleClass().add("subtle");
            excludeCheckboxList.getChildren().add(none);
            return;
        }
        for (MemberDto m : members) {
            CheckBox cb = new CheckBox(m.username);
            cb.selectedProperty().addListener((obs, was, isNow) -> {
                if (isNow) excludedUserIds.add(m.userId); else excludedUserIds.remove(m.userId);
                updateExcludeBadge();
            });
            excludeCheckboxList.getChildren().add(cb);
        }
    }

    private void updateExcludeBadge() {
        if (excludeCountBadge == null) return;
        int n = excludedUserIds.size();
        excludeCountBadge.setText(String.valueOf(n));
        excludeCountBadge.setManaged(n > 0);
        excludeCountBadge.setVisible(n > 0);
    }

    @FXML
    private void onToggleExcludePicker() {
        if (excludePickerBox == null) return;
        boolean nowVisible = !excludePickerBox.isVisible();
        excludePickerBox.setManaged(nowVisible);
        excludePickerBox.setVisible(nowVisible);
    }

    private void onFlagPost(Post p) {
        TextInputDialog dialog = new TextInputDialog();
        dialog.setTitle("Report post");
        dialog.setHeaderText("Report this post to a system admin");
        dialog.setContentText("Reason:");
        Optional<String> result = dialog.showAndWait();
        result.ifPresent(reason -> {
            if (reason.isBlank()) return;
            String token = Session.authToken();
            if (token == null) return;
            Thread t = new Thread(() -> {
                try {
                    api.flagPost(token, p.getPostId(), reason.trim());
                    Platform.runLater(() -> infoAlert("Reported", "Post reported. A system admin will review it."));
                } catch (ApiException e) {
                    Platform.runLater(() -> errorAlert("Couldn't report post", e.getMessage()));
                } catch (Exception e) {
                    if (e instanceof InterruptedException) Thread.currentThread().interrupt();
                }
            }, "flag-post-api");
            t.setDaemon(true);
            t.start();
        });
    }

    private void onEditPost(Post p) {
        TextArea area = new TextArea(p.getContent());
        area.setWrapText(true);
        area.setPrefRowCount(6);

        Dialog<String> dialog = new Dialog<>();
        dialog.setTitle("Edit reply");
        dialog.getDialogPane().setContent(area);
        dialog.getDialogPane().getButtonTypes().addAll(ButtonType.OK, ButtonType.CANCEL);
        dialog.setResultConverter(btn -> btn == ButtonType.OK ? area.getText().trim() : null);

        dialog.showAndWait().ifPresent(newContent -> {
            if (newContent.isBlank() || newContent.equals(p.getContent())) return;
            String token = Session.authToken();
            if (token == null) return;
            Thread t = new Thread(() -> {
                try {
                    api.updatePost(token, p.getPostId(), newContent);
                    postDao.updateContentLocal(p.getPostId(), newContent);
                    List<Post> fresh = postDao.listByTopic(topic.getTopicId());
                    Platform.runLater(() -> renderPosts(fresh));
                } catch (ApiException e) {
                    Platform.runLater(() -> errorAlert("Couldn't update post", e.getMessage()));
                } catch (Exception e) {
                    if (e instanceof InterruptedException) Thread.currentThread().interrupt();
                }
            }, "edit-post-api");
            t.setDaemon(true);
            t.start();
        });
    }

    private void onDeletePost(Post p) {
        Alert confirm = new Alert(Alert.AlertType.CONFIRMATION,
                "Delete this post? This can't be undone.", ButtonType.YES, ButtonType.NO);
        confirm.setHeaderText(null);
        confirm.showAndWait().ifPresent(choice -> {
            if (choice != ButtonType.YES) return;
            String token = Session.authToken();
            if (token == null) return;
            Thread t = new Thread(() -> {
                try {
                    api.deletePost(token, p.getPostId());
                    postDao.deleteLocal(p.getPostId());
                    List<Post> fresh = postDao.listByTopic(topic.getTopicId());
                    Platform.runLater(() -> renderPosts(fresh));
                } catch (ApiException e) {
                    Platform.runLater(() -> errorAlert("Couldn't delete post", e.getMessage()));
                } catch (Exception e) {
                    if (e instanceof InterruptedException) Thread.currentThread().interrupt();
                }
            }, "delete-post-api");
            t.setDaemon(true);
            t.start();
        });
    }

    @FXML
    private void onEditTopic() {
        if (topic == null) return;
        long serverTopicId = topicDao.serverIdFor(topic.getTopicId());
        if (serverTopicId <= 0) { errorAlert("Can't edit yet", "This topic hasn't synced to the server yet."); return; }

        TextField titleField = new TextField(topic.getTitle());
        TextField categoryField = new TextField(topic.getCategory() == null ? "" : topic.getCategory());
        TextArea contentArea = new TextArea(postDao.listByTopic(topic.getTopicId()).stream()
                .filter(p -> p.getParentPostId() == null)
                .findFirst().map(Post::getContent).orElse(""));
        contentArea.setWrapText(true);
        contentArea.setPrefRowCount(6);

        VBox content = new VBox(8,
                new Label("Title"), titleField,
                new Label("Category"), categoryField,
                new Label("Opening post content"), contentArea);
        content.setPadding(new Insets(8));

        Dialog<ButtonType> dialog = new Dialog<>();
        dialog.setTitle("Edit topic");
        dialog.getDialogPane().setContent(content);
        dialog.getDialogPane().getButtonTypes().addAll(ButtonType.OK, ButtonType.CANCEL);

        dialog.showAndWait().ifPresent(btn -> {
            if (btn != ButtonType.OK) return;
            String title = titleField.getText().trim();
            String category = categoryField.getText().trim();
            String contentText = contentArea.getText().trim();
            if (title.isEmpty() || contentText.isEmpty()) {
                errorAlert("Missing fields", "Title and content are both required.");
                return;
            }
            String token = Session.authToken();
            if (token == null) return;
            Thread t = new Thread(() -> {
                try {
                    api.updateTopic(token, serverTopicId, title, category, topic.getGroupId(), contentText);
                    topicDao.updateLocal(topic.getTopicId(), title, category);
                    topic.setTitle(title);
                    topic.setCategory(category);
                    Platform.runLater(() -> {
                        if (topicTitleLabel != null) topicTitleLabel.setText(title);
                        renderPosts(postDao.listByTopic(topic.getTopicId()));
                    });
                } catch (ApiException e) {
                    Platform.runLater(() -> errorAlert("Couldn't update topic", e.getMessage()));
                } catch (Exception e) {
                    if (e instanceof InterruptedException) Thread.currentThread().interrupt();
                }
            }, "edit-topic-api");
            t.setDaemon(true);
            t.start();
        });
    }

    @FXML
    private void onDeleteTopic() {
        if (topic == null) return;
        long serverTopicId = topicDao.serverIdFor(topic.getTopicId());
        if (serverTopicId <= 0) { errorAlert("Can't delete yet", "This topic hasn't synced to the server yet."); return; }

        Alert confirm = new Alert(Alert.AlertType.CONFIRMATION,
                "Delete this topic and all its replies? This can't be undone.", ButtonType.YES, ButtonType.NO);
        confirm.setHeaderText(null);
        confirm.showAndWait().ifPresent(choice -> {
            if (choice != ButtonType.YES) return;
            String token = Session.authToken();
            if (token == null) return;
            Thread t = new Thread(() -> {
                try {
                    api.deleteTopic(token, serverTopicId);
                    topicDao.deleteLocal(topic.getTopicId());
                    Platform.runLater(TopicDetailController.this::onBack);
                } catch (ApiException e) {
                    Platform.runLater(() -> errorAlert("Couldn't delete topic", e.getMessage()));
                } catch (Exception e) {
                    if (e instanceof InterruptedException) Thread.currentThread().interrupt();
                }
            }, "delete-topic-api");
            t.setDaemon(true);
            t.start();
        });
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
        List<Long> excludedForThisReply = new java.util.ArrayList<>(excludedUserIds);

        Thread worker = new Thread(() -> {
            // Online — post to the server and cache the result.
            if (token != null && !token.isBlank() && serverTopicId > 0) {
                try {
                    PostDto dto = api.createPost(token, serverTopicId, body, null, excludedForThisReply);
                    postDao.upsertFromServer(dto);
                    if (dto.excluded_usernames != null) excludedUsernamesByPost.put(dto.post_id, dto.excluded_usernames);
                    List<Post> fresh = postDao.listByTopic(topic.getTopicId());
                    Platform.runLater(() -> { renderPosts(fresh); resetExcludePicker(); });
                    return;
                } catch (Exception offline) {
                    // fall through to the local queue — excluded_users isn't
                    // supported for queued offline replies (server-only feature)
                }
            }
            // Offline — save locally and queue for sync.
            postDao.create(topic.getTopicId(), authorId, null, body);
            List<Post> fresh = postDao.listByTopic(topic.getTopicId());
            Platform.runLater(() -> { renderPosts(fresh); resetExcludePicker(); });
        }, "aces-reply");
        worker.setDaemon(true);
        worker.start();
    }

    private void resetExcludePicker() {
        excludedUserIds.clear();
        updateExcludeBadge();
        if (excludeCheckboxList != null) {
            for (var node : excludeCheckboxList.getChildren()) {
                if (node instanceof CheckBox cb) cb.setSelected(false);
            }
        }
        if (excludePickerBox != null) {
            excludePickerBox.setManaged(false);
            excludePickerBox.setVisible(false);
        }
    }

    @FXML
    private void onBack() {
        SceneManager.show("ForumDashboard", "Smart Discussion Forum");
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

    private void infoAlert(String header, String msg) {
        Alert a = new Alert(Alert.AlertType.INFORMATION, msg);
        a.setHeaderText(header);
        a.showAndWait();
    }

    private void errorAlert(String header, String msg) {
        Alert a = new Alert(Alert.AlertType.ERROR, msg == null ? "Something went wrong." : msg);
        a.setHeaderText(header);
        a.showAndWait();
    }
}