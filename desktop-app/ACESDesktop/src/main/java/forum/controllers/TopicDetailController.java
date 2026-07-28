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
import javafx.scene.layout.HBox;
import javafx.scene.layout.Region;
import javafx.scene.layout.VBox;
import javafx.stage.FileChooser;
import javafx.scene.control.Button;
import javafx.scene.control.ScrollPane;
import javafx.scene.layout.GridPane;
import javafx.scene.control.CheckBox;
import javafx.scene.layout.FlowPane;
import javafx.scene.control.Hyperlink;
import javafx.scene.Node;

import java.io.File;
import java.io.PrintWriter;
import java.util.List;
import java.util.Arrays;
import java.util.ArrayList;
import java.util.Collections;
import java.util.HashMap;
import java.util.Map;

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
    @FXML private Button hideButton;
    @FXML private javafx.scene.shape.SVGPath hideIcon;
    @FXML private ScrollPane scrollPane;
    @FXML private HBox hidePanel;
    @FXML private FlowPane hideMembersFlow;

    private boolean isHiddenMode = false;
    private List<forum.api.dto.MemberDto> excludableMembers = Collections.emptyList();
    private final Map<Long, List<String>> excludedUsernamesByPostId = new HashMap<>();

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
        scrollToBottom();
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
                if (detail.posts != null) for (PostDto p : detail.posts) {
                    cacheExcludedUsers(p);
                    postDao.upsertFromServer(p);
                }
                List<Post> fresh = postDao.listByTopic(topic.getTopicId());
                Platform.runLater(() -> {
                    setExcludableMembers(detail.groupMembers);
                    renderPosts(fresh);
                    scrollToBottom();
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
        if (repliesCountLabel != null) repliesCountLabel.setText(Math.max(0, posts.size() - 1) + " Replies");
        if (posts.isEmpty()) {
            Label empty = new Label("No replies yet. Be the first to respond.");
            empty.getStyleClass().add("muted");
            postList.getChildren().add(empty);
            return;
        }
        
        long currentUserId = 0;
        User u = Session.currentUser();
        if (u != null) {
            currentUserId = u.getUserId();
        }

        for (int i = 0; i < posts.size(); i++) {
            Post p = posts.get(i);
            if (i == 0) {
                postList.getChildren().add(questionBlock(p));
            } else {
                postList.getChildren().add(chatBubble(p, p.getAuthorId() == currentUserId));
            }
        }
    }

    private VBox questionBlock(Post p) {
        VBox card = new VBox(16);
        card.getStyleClass().add("card");
        card.setStyle("-fx-border-color: #f3f4f6; -fx-border-width: 1; -fx-border-radius: 8; -fx-padding: 24; -fx-background-color: white;");

        // Top row: Avatar, Name, "Author" badge, Date on right
        HBox topRow = new HBox(12);
        topRow.setAlignment(javafx.geometry.Pos.CENTER_LEFT);
        
        String authorName = p.getAuthorName() == null ? "Unknown" : p.getAuthorName();
        Label avatar = new Label(authorName.isBlank() ? "?" : String.valueOf(authorName.charAt(0)).toUpperCase());
        avatar.setStyle("-fx-background-color: #4f46e5; -fx-text-fill: white; -fx-font-weight: bold; -fx-font-size: 16px; -fx-background-radius: 50; -fx-min-width: 40; -fx-min-height: 40; -fx-alignment: center;");
        
        VBox nameBox = new VBox(2);
        HBox nameLine = new HBox(8);
        nameLine.setAlignment(javafx.geometry.Pos.CENTER_LEFT);
        Label nameLbl = new Label(authorName);
        nameLbl.setStyle("-fx-font-size: 14px; -fx-font-weight: bold; -fx-text-fill: #111827;");
        Label authorBadge = new Label("Author");
        authorBadge.setStyle("-fx-background-color: #6366f1; -fx-text-fill: white; -fx-font-size: 10px; -fx-font-weight: bold; -fx-padding: 2 6; -fx-background-radius: 12;");
        nameLine.getChildren().addAll(nameLbl, authorBadge);
        
        String dateStr = formatTime(p.getCreatedAt());
        Label dateLbl = new Label(dateStr);
        dateLbl.setStyle("-fx-font-size: 12px; -fx-text-fill: #6b7280;");
        
        nameBox.getChildren().addAll(nameLine, dateLbl);
        
        Region spacer = new Region();
        HBox.setHgrow(spacer, javafx.scene.layout.Priority.ALWAYS);
        
        Label tag = new Label();
        if (topic != null && topic.getCategory() != null) {
             tag.setText(topic.getCategory());
             tag.setStyle("-fx-background-color: #f3f4f6; -fx-text-fill: #6b7280; -fx-font-size: 11px; -fx-font-weight: bold; -fx-padding: 4 8; -fx-background-radius: 12;");
        }
        
        topRow.getChildren().addAll(avatar, nameBox, spacer, tag);
        
        Label title = new Label(topic != null ? topic.getTitle() : "");
        title.setStyle("-fx-font-size: 20px; -fx-font-weight: bold; -fx-text-fill: #111827;");
        title.setWrapText(true);
        
        Label body = new Label(p.getContent());
        body.setStyle("-fx-font-size: 14px; -fx-text-fill: #374151; -fx-line-spacing: 4px;");
        body.setWrapText(true);
        
        card.getChildren().addAll(topRow, title, body);
        return card;
    }

    private HBox chatBubble(Post p, boolean isOutgoing) {
        HBox row = new HBox(8);
        row.setPadding(new javafx.geometry.Insets(0, 0, 16, 0));
        
        String authorName = p.getAuthorName() == null ? "Unknown" : p.getAuthorName();
        Label avatar = new Label(authorName.isBlank() ? "?" : String.valueOf(authorName.charAt(0)).toUpperCase());
        avatar.setStyle("-fx-background-color: #a855f7; -fx-text-fill: white; -fx-font-weight: bold; -fx-font-size: 12px; -fx-background-radius: 50; -fx-min-width: 28; -fx-min-height: 28; -fx-alignment: center;");
        
        javafx.scene.layout.StackPane bubbleWrapper = new javafx.scene.layout.StackPane();
        bubbleWrapper.setMaxWidth(500);

        VBox bubble = new VBox(4);
        bubble.setStyle(isOutgoing 
            ? "-fx-background-color: #bbf7d0; -fx-background-radius: 16 16 0 16; -fx-padding: 12 40 12 16;" 
            : "-fx-background-color: white; -fx-border-color: #e5e7eb; -fx-border-width: 1; -fx-border-radius: 16 16 16 0; -fx-background-radius: 16 16 16 0; -fx-padding: 12 40 12 16;");
        
        if (!isOutgoing) {
            Label nameLbl = new Label(authorName);
            nameLbl.setStyle("-fx-font-size: 12px; -fx-font-weight: bold; -fx-text-fill: #9333ea;");
            bubble.getChildren().add(nameLbl);
        }
        
        Node bodyNode;
        String text = p.getContent();
        if (text != null && text.contains("[Attached: ")) {
            VBox container = new VBox(8);
            int start = text.indexOf("[Attached: ");
            int end = text.indexOf("]", start);
            if (end > start) {
                String before = text.substring(0, start).trim();
                String attachedText = text.substring(start + 11, end);
                String[] attachParts = attachedText.split("\\|");
                String fileName = attachParts[0];
                String absPath = attachParts.length > 1 ? attachParts[1] : fileName;
                
                String after = text.substring(end + 1).trim();
                
                if (!before.isEmpty()) {
                    Label l1 = new Label(before);
                    l1.setStyle("-fx-font-size: 14px; -fx-text-fill: #1f2937;");
                    l1.setWrapText(true);
                    container.getChildren().add(l1);
                }
                
                Hyperlink link = new Hyperlink("📎 " + fileName);
                link.setStyle("-fx-font-size: 13px; -fx-text-fill: #2563eb; -fx-underline: true;");
                link.setOnAction(e -> {
                    try {
                        File f = new File(absPath);
                        if (f.exists()) {
                            java.awt.Desktop.getDesktop().open(f);
                        } else {
                            showThemedWarning("Not Found", "Attachment not found locally: " + fileName);
                        }
                    } catch (Exception ex) {
                        showThemedWarning("Error", "Could not open attachment.");
                    }
                });
                container.getChildren().add(link);
                
                if (!after.isEmpty()) {
                    Label l2 = new Label(after);
                    l2.setStyle("-fx-font-size: 14px; -fx-text-fill: #1f2937;");
                    l2.setWrapText(true);
                    container.getChildren().add(l2);
                }
            } else {
                Label l = new Label(text);
                l.setStyle("-fx-font-size: 14px; -fx-text-fill: #1f2937;");
                l.setWrapText(true);
                container.getChildren().add(l);
            }
            bodyNode = container;
        } else {
            Label body = new Label(text);
            body.setStyle("-fx-font-size: 14px; -fx-text-fill: #1f2937;");
            body.setWrapText(true);
            bodyNode = body;
        }
        
        Label meta = new Label(formatTime(p.getCreatedAt()));
        meta.setStyle("-fx-font-size: 10px; -fx-text-fill: #6b7280;");
        HBox metaRow = new HBox(meta);
        metaRow.setAlignment(javafx.geometry.Pos.CENTER_RIGHT);
        
        bubble.getChildren().add(bodyNode);
        List<String> excludedUsers = excludedUsernamesByPostId.getOrDefault(p.getPostId(), Collections.emptyList());
        if (isOutgoing && !excludedUsers.isEmpty()) {
            // Matches web's forum/show.blade.php exactly: "🔒 Hidden from X, Y"
            // on an amber pill, shown only to the post's own author.
            Label excludedLabel = new Label("🔒 Hidden from " + String.join(", ", excludedUsers));
            excludedLabel.setStyle(
                "-fx-font-size: 10px; -fx-text-fill: #b45309; -fx-background-color: #fef3c7; " +
                "-fx-background-radius: 999; -fx-padding: 2 8 2 8;"
            );
            excludedLabel.setWrapText(true);
            HBox excludedLabelRow = new HBox(excludedLabel);
            excludedLabelRow.setAlignment(javafx.geometry.Pos.CENTER_RIGHT);
            bubble.getChildren().add(excludedLabelRow);
        }
        bubble.getChildren().add(metaRow);
        
        HBox topButtons = new HBox(4);
        topButtons.setAlignment(javafx.geometry.Pos.TOP_RIGHT);
        topButtons.setPickOnBounds(false);
        javafx.scene.layout.StackPane.setMargin(topButtons, new javafx.geometry.Insets(8, 8, 0, 0));
        
        Button reportBtn = new Button();
        javafx.scene.shape.SVGPath flagIcon = new javafx.scene.shape.SVGPath();
        flagIcon.setContent("M14.4 6L14 4H5v17h2v-7h5.6l.4 2h7V6z");
        flagIcon.setFill(javafx.scene.paint.Color.web("#f43f5e"));
        flagIcon.setScaleX(0.6);
        flagIcon.setScaleY(0.6);
        reportBtn.setGraphic(flagIcon);
        reportBtn.setStyle("-fx-background-color: #e5e7eb; -fx-background-radius: 50; -fx-padding: 0; -fx-min-width: 24; -fx-min-height: 24; -fx-cursor: hand;");
        reportBtn.setOnAction(e -> onReportPost(p));

        if (isOutgoing) {
            Button menuBtn = new Button();
            javafx.scene.shape.SVGPath dotsIcon = new javafx.scene.shape.SVGPath();
            dotsIcon.setContent("M12 8c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm0 2c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm0 6c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z");
            dotsIcon.setFill(javafx.scene.paint.Color.web("#065f46"));
            dotsIcon.setScaleX(0.6);
            dotsIcon.setScaleY(0.6);
            menuBtn.setGraphic(dotsIcon);
            menuBtn.setStyle("-fx-background-color: #86efac; -fx-background-radius: 50; -fx-padding: 0; -fx-min-width: 24; -fx-min-height: 24; -fx-cursor: hand;");
            
            javafx.scene.control.ContextMenu contextMenu = new javafx.scene.control.ContextMenu();
            javafx.scene.control.MenuItem editItem = new javafx.scene.control.MenuItem("✏️ Edit");
            editItem.setOnAction(e -> onEditPost(p));
            javafx.scene.control.MenuItem deleteItem = new javafx.scene.control.MenuItem("🗑️ Delete");
            deleteItem.setStyle("-fx-text-fill: #dc2626;");
            deleteItem.setOnAction(e -> onDeletePost(p));
            contextMenu.getItems().addAll(editItem, deleteItem);
            
            menuBtn.setOnMouseClicked(e -> {
                contextMenu.show(menuBtn, e.getScreenX(), e.getScreenY());
            });
            topButtons.getChildren().add(menuBtn);
        } else {
            topButtons.getChildren().add(reportBtn);
        }
        
        bubbleWrapper.getChildren().addAll(bubble, topButtons);
        
        if (isOutgoing) {
            row.setAlignment(javafx.geometry.Pos.CENTER_RIGHT);
            row.getChildren().add(bubbleWrapper);
        } else {
            row.setAlignment(javafx.geometry.Pos.TOP_LEFT);
            row.getChildren().addAll(avatar, bubbleWrapper);
        }
        
        return row;
    }

    @FXML
private void onReply() {
    if (composerField == null || topic == null) return;
    String content = composerField.getText();
    if (content == null || content.isBlank()) return;
    composerField.clear();

    List<Long> excludedUserIds = selectedExcludedUserIds();
    // NOTE: exclusion is recorded server-side via the excludedUserIds
    // parameter passed to api.createPost() below — the reply content
    // itself must stay exactly what the user typed. Previously this
    // prepended "[Hidden from Students] " into the actual post body,
    // which permanently corrupted the stored content (visible to
    // everyone, including the excluded students once un-excluded, and
    // mismatched with the web side which never touches post content).
    if (isHiddenMode) toggleHideMode(); // reset after send

    User u = Session.currentUser();
    long authorId = (u != null) ? u.getUserId() : 0;
    String token = Session.authToken();
    long serverTopicId = topicDao.serverIdFor(topic.getTopicId());
    String body = content.trim();

    Thread worker = new Thread(() -> {
        // Online — post to the server and cache the result.
        if (token != null && !token.isBlank() && serverTopicId > 0) {
            try {
                PostDto dto = api.createPost(token, serverTopicId, body, null, excludedUserIds);
                cacheExcludedUsers(dto);
                postDao.upsertFromServer(dto);
                List<Post> fresh = postDao.listByTopic(topic.getTopicId());
                Platform.runLater(() -> {
                    renderPosts(fresh);
                    scrollToBottom();
                });
                return;
            } catch (Exception offline) {
                // fall through to the local queue
            }
        }
        // Offline — save locally and queue for sync.
        postDao.create(topic.getTopicId(), authorId, null, body);
        List<Post> fresh = postDao.listByTopic(topic.getTopicId());
        Platform.runLater(() -> {
            renderPosts(fresh);
            scrollToBottom();
        });
    }, "aces-reply");
    worker.setDaemon(true);
    worker.start();
}
    
    private void scrollToBottom() {
        if (scrollPane != null) {
            // Delay the scroll until after JavaFX finishes its layout pulse for the new nodes
            Platform.runLater(() -> {
                scrollPane.applyCss();
                scrollPane.layout();
                scrollPane.setVvalue(1.0);
            });
        }
    }

    @FXML
    private void onAttach() {
        FileChooser fc = new FileChooser();
        fc.setTitle("Select Attachment");
        File file = fc.showOpenDialog(null);
        if (file != null) {
            String current = composerField.getText() == null ? "" : composerField.getText();
            String prefix = current.isEmpty() ? "" : current + " ";
            composerField.setText(prefix + "[Attached: " + file.getName() + "|" + file.getAbsolutePath().replace("\\", "\\\\") + "]");
        }
    }

    @FXML
    private void onHide() {
        toggleHideMode();
    }
    
    private void toggleHideMode() {
        isHiddenMode = !isHiddenMode;
        if (hideIcon != null) {
            hideIcon.setStroke(isHiddenMode ? javafx.scene.paint.Color.RED : javafx.scene.paint.Color.web("#9ca3af"));
        }
        if (hidePanel != null && hideMembersFlow != null) {
            hidePanel.setVisible(isHiddenMode);
            hidePanel.setManaged(isHiddenMode);
            
            if (isHiddenMode) populateHideMembers();
        }
    }

    private void setExcludableMembers(List<forum.api.dto.MemberDto> members) {
        if (members == null) {
            excludableMembers = Collections.emptyList();
        } else {
            excludableMembers = members.stream()
                    // Do not offer a group administrator or any known non-student role.
                    .filter(member -> member.role == null || "member".equalsIgnoreCase(member.role))
                    .toList();
        }
        if (isHiddenMode) populateHideMembers();
    }

    private void populateHideMembers() {
        if (hideMembersFlow == null) return;
        hideMembersFlow.getChildren().clear();
        for (forum.api.dto.MemberDto member : excludableMembers) {
            CheckBox checkBox = new CheckBox(member.username);
            checkBox.setUserData(member.userId);
            checkBox.setStyle("-fx-background-color: white; -fx-border-color: #fde047; -fx-border-radius: 12; -fx-padding: 4 8; -fx-text-fill: #4b5563; -fx-font-size: 13px;");
            hideMembersFlow.getChildren().add(checkBox);
        }
    }

    private List<Long> selectedExcludedUserIds() {
        if (!isHiddenMode || hideMembersFlow == null) return Collections.emptyList();
        List<Long> selected = new ArrayList<>();
        for (Node node : hideMembersFlow.getChildren()) {
            if (node instanceof CheckBox checkBox && checkBox.isSelected() && checkBox.getUserData() instanceof Long id) {
                selected.add(id);
            }
        }
        return selected;
    }

    private void cacheExcludedUsers(PostDto post) {
        if (post == null || post.excluded_usernames == null || post.excluded_usernames.isEmpty()) return;
        excludedUsernamesByPostId.put(post.post_id, List.copyOf(post.excluded_usernames));
    }

    @FXML
    private void onBack() {
        SceneManager.show("ForumDashboard", "ACES");
    }

    @FXML
    private void onExportPdf() {
        if (topic == null) return;
        FileChooser fc = new FileChooser();
        fc.setTitle("Export Discussion to PDF (Text format)");
        fc.getExtensionFilters().add(new FileChooser.ExtensionFilter("Text Documents", "*.txt"));
        fc.setInitialFileName("Topic_" + topic.getTopicId() + "_Export.txt");
        File file = fc.showSaveDialog(null);
        if (file != null) {
            try (PrintWriter out = new PrintWriter(file)) {
                out.println("Topic: " + topic.getTitle());
                out.println("Category: " + topic.getCategory());
                out.println("-----------------------------------------");
                List<Post> posts = postDao.listByTopic(topic.getTopicId());
                for (Post p : posts) {
                    out.println("[" + (p.getCreatedAt() != null ? p.getCreatedAt() : "Unknown Time") + "] " +
                            (p.getAuthorName() != null ? p.getAuthorName() : "Unknown") + ":");
                    out.println(p.getContent());
                    out.println();
                }
                showThemedWarning("Success", "Discussion successfully exported to " + file.getName());
            } catch (Exception e) {
                showThemedWarning("Error", "Failed to export: " + e.getMessage());
            }
        }
    }

    @FXML
    private void onShare() {
        if (topic == null) return;
        javafx.stage.Stage stage = new javafx.stage.Stage();
        stage.initStyle(javafx.stage.StageStyle.TRANSPARENT);
        stage.initModality(javafx.stage.Modality.APPLICATION_MODAL);
        
        VBox root = new VBox(20);
        root.setStyle("-fx-background-color: white; -fx-background-radius: 16; -fx-border-color: #e5e7eb; -fx-border-radius: 16; -fx-border-width: 1; -fx-padding: 24;");
        root.setPrefWidth(400);
        
        HBox header = new HBox();
        header.setAlignment(javafx.geometry.Pos.CENTER_LEFT);
        Label titleLbl = new Label("Share this topic");
        titleLbl.setStyle("-fx-font-size: 18px; -fx-font-weight: bold; -fx-text-fill: #1f2937;");
        Region spacer = new Region();
        HBox.setHgrow(spacer, javafx.scene.layout.Priority.ALWAYS);
        Button closeBtn = new Button("✕");
        closeBtn.setStyle("-fx-background-color: transparent; -fx-cursor: hand; -fx-font-size: 16px; -fx-text-fill: #9ca3af; -fx-padding: 0;");
        closeBtn.setOnAction(e -> stage.close());
        header.getChildren().addAll(titleLbl, spacer, closeBtn);
        
        GridPane grid = new GridPane();
        grid.setHgap(16);
        grid.setVgap(16);
        grid.setAlignment(javafx.geometry.Pos.CENTER);
        
        Button btnX = createSocialButton("X (Twitter)", "M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 22.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z", "#000000");
        Button btnLi = createSocialButton("LinkedIn", "M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z", "#0077b5");
        Button btnFb = createSocialButton("Facebook", "M9 8h-3v4h3v12h5v-12h3.642l.358-4h-4v-1.667c0-.955.192-1.333 1.115-1.333h2.885v-5h-3.808c-3.596 0-5.192 1.583-5.192 4.615v3.385z", "#1877f2");
        Button btnWa = createSocialButton("WhatsApp", "M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z", "#22c55e");
        
        String webUrl = forum.config.DatabaseConfig.API_BASE_URL.replace("/api", "") + "/topics/" + topic.getTopicId();
        String encodedUrl = java.net.URLEncoder.encode(webUrl, java.nio.charset.StandardCharsets.UTF_8);
        String encodedTitle = java.net.URLEncoder.encode(topic.getTitle(), java.nio.charset.StandardCharsets.UTF_8);

        btnX.setOnAction(e -> openWebLink("https://twitter.com/intent/tweet?text=" + encodedTitle + "&url=" + encodedUrl));
        btnLi.setOnAction(e -> openWebLink("https://www.linkedin.com/sharing/share-offsite/?url=" + encodedUrl));
        btnFb.setOnAction(e -> openWebLink("https://www.facebook.com/sharer/sharer.php?u=" + encodedUrl));
        btnWa.setOnAction(e -> openWebLink("https://api.whatsapp.com/send?text=" + encodedTitle + "%20" + encodedUrl));

        grid.add(btnX, 0, 0);
        grid.add(btnLi, 1, 0);
        grid.add(btnFb, 0, 1);
        grid.add(btnWa, 1, 1);
        
        Button copyLink = createSocialButton("Copy Link", "M13.293 7.293a1 1 0 00-1.414 1.414L14.586 11H7a1 1 0 100 2h7.586l-2.707 2.293a1 1 0 101.414 1.414l4.5-4.5a1 1 0 000-1.414l-4.5-4.5z", "#6b7280");
        copyLink.setMaxWidth(Double.MAX_VALUE);
        copyLink.setOnAction(e -> {
            javafx.scene.input.ClipboardContent cc = new javafx.scene.input.ClipboardContent();
            cc.putString(webUrl);
            javafx.scene.input.Clipboard.getSystemClipboard().setContent(cc);
            showThemedWarning("Success", "Link copied to clipboard!");
            stage.close();
        });
        
        root.getChildren().addAll(header, grid, copyLink);
        
        javafx.scene.Scene scene = new javafx.scene.Scene(root, javafx.scene.paint.Color.TRANSPARENT);
        stage.setScene(scene);
        stage.show();
    }

    private Button createSocialButton(String text, String svgPath, String color) {
        Button btn = new Button(text);
        btn.setStyle("-fx-background-color: white; -fx-border-color: #e5e7eb; -fx-border-radius: 8; -fx-padding: 10 20; -fx-cursor: hand; -fx-text-fill: #374151; -fx-font-weight: bold; -fx-font-size: 14px; -fx-alignment: center-left;");
        btn.setPrefWidth(160);
        
        javafx.scene.shape.SVGPath icon = new javafx.scene.shape.SVGPath();
        icon.setContent(svgPath);
        icon.setFill(javafx.scene.paint.Color.web(color));
        icon.setScaleX(0.7);
        icon.setScaleY(0.7);
        btn.setGraphic(icon);
        btn.setGraphicTextGap(12);
        return btn;
    }

    private void showThemedWarning(String title, String message) {
        javafx.stage.Stage stage = new javafx.stage.Stage();
        stage.initStyle(javafx.stage.StageStyle.TRANSPARENT);
        stage.initModality(javafx.stage.Modality.APPLICATION_MODAL);
        
        VBox root = new VBox(16);
        root.setStyle("-fx-background-color: white; -fx-background-radius: 12; -fx-border-color: #e5e7eb; -fx-border-radius: 12; -fx-border-width: 1; -fx-padding: 24;");
        root.setAlignment(javafx.geometry.Pos.CENTER);
        root.setPrefWidth(350);
        
        HBox header = new HBox();
        header.setAlignment(javafx.geometry.Pos.CENTER_LEFT);
        Label titleLbl = new Label(title);
        titleLbl.setStyle("-fx-font-size: 16px; -fx-font-weight: bold; -fx-text-fill: #1f2937;");
        Region spacer = new Region();
        HBox.setHgrow(spacer, javafx.scene.layout.Priority.ALWAYS);
        Button closeBtn = new Button("✕");
        closeBtn.setStyle("-fx-background-color: transparent; -fx-cursor: hand; -fx-font-size: 16px; -fx-text-fill: #9ca3af; -fx-padding: 0;");
        closeBtn.setOnAction(e -> stage.close());
        header.getChildren().addAll(titleLbl, spacer, closeBtn);
        
        Label msgLbl = new Label(message);
        msgLbl.setStyle("-fx-font-size: 14px; -fx-text-fill: #4b5563;");
        msgLbl.setWrapText(true);
        msgLbl.setMaxWidth(Double.MAX_VALUE);
        
        Button okBtn = new Button("OK");
        okBtn.setStyle("-fx-background-color: #22c55e; -fx-text-fill: white; -fx-border-radius: 6; -fx-background-radius: 6; -fx-padding: 8 24; -fx-cursor: hand; -fx-font-weight: bold;");
        okBtn.setOnAction(e -> stage.close());
        
        HBox btnRow = new HBox(okBtn);
        btnRow.setAlignment(javafx.geometry.Pos.CENTER_RIGHT);
        
        root.getChildren().addAll(header, msgLbl, btnRow);
        
        javafx.scene.Scene scene = new javafx.scene.Scene(root, javafx.scene.paint.Color.TRANSPARENT);
        stage.setScene(scene);
        stage.show();
    }

    private void showThemedInput(String title, String prompt, String defaultValue, java.util.function.Consumer<String> onSave) {
        javafx.stage.Stage stage = new javafx.stage.Stage();
        stage.initStyle(javafx.stage.StageStyle.TRANSPARENT);
        stage.initModality(javafx.stage.Modality.APPLICATION_MODAL);
        
        VBox root = new VBox(16);
        root.setStyle("-fx-background-color: white; -fx-background-radius: 12; -fx-border-color: #e5e7eb; -fx-border-radius: 12; -fx-border-width: 1; -fx-padding: 24;");
        root.setAlignment(javafx.geometry.Pos.CENTER);
        root.setPrefWidth(400);
        
        HBox header = new HBox();
        header.setAlignment(javafx.geometry.Pos.CENTER_LEFT);
        Label titleLbl = new Label(title);
        titleLbl.setStyle("-fx-font-size: 16px; -fx-font-weight: bold; -fx-text-fill: #1f2937;");
        Region spacer = new Region();
        HBox.setHgrow(spacer, javafx.scene.layout.Priority.ALWAYS);
        Button closeBtn = new Button("✕");
        closeBtn.setStyle("-fx-background-color: transparent; -fx-cursor: hand; -fx-font-size: 16px; -fx-text-fill: #9ca3af; -fx-padding: 0;");
        closeBtn.setOnAction(e -> stage.close());
        header.getChildren().addAll(titleLbl, spacer, closeBtn);
        
        Label promptLbl = new Label(prompt);
        promptLbl.setStyle("-fx-font-size: 14px; -fx-text-fill: #4b5563;");
        promptLbl.setMaxWidth(Double.MAX_VALUE);
        
        javafx.scene.control.TextArea input = new javafx.scene.control.TextArea(defaultValue);
        input.setPrefRowCount(3);
        input.setWrapText(true);
        input.setStyle("-fx-font-size: 14px; -fx-background-radius: 6; -fx-border-radius: 6; -fx-border-color: #d1d5db; -fx-background-color: white;");
        
        Button saveBtn = new Button("Save");
        saveBtn.setStyle("-fx-background-color: #4f46e5; -fx-text-fill: white; -fx-border-radius: 6; -fx-background-radius: 6; -fx-padding: 8 24; -fx-cursor: hand; -fx-font-weight: bold;");
        saveBtn.setOnAction(e -> {
            stage.close();
            onSave.accept(input.getText());
        });
        
        HBox btnRow = new HBox(saveBtn);
        btnRow.setAlignment(javafx.geometry.Pos.CENTER_RIGHT);
        
        root.getChildren().addAll(header, promptLbl, input, btnRow);
        javafx.scene.Scene scene = new javafx.scene.Scene(root, javafx.scene.paint.Color.TRANSPARENT);
        stage.setScene(scene);
        stage.show();
    }

    private void showThemedConfirm(String title, String message, Runnable onConfirm) {
        javafx.stage.Stage stage = new javafx.stage.Stage();
        stage.initStyle(javafx.stage.StageStyle.TRANSPARENT);
        stage.initModality(javafx.stage.Modality.APPLICATION_MODAL);
        
        VBox root = new VBox(16);
        root.setStyle("-fx-background-color: white; -fx-background-radius: 12; -fx-border-color: #e5e7eb; -fx-border-radius: 12; -fx-border-width: 1; -fx-padding: 24;");
        root.setAlignment(javafx.geometry.Pos.CENTER);
        root.setPrefWidth(350);
        
        HBox header = new HBox();
        header.setAlignment(javafx.geometry.Pos.CENTER_LEFT);
        Label titleLbl = new Label(title);
        titleLbl.setStyle("-fx-font-size: 16px; -fx-font-weight: bold; -fx-text-fill: #1f2937;");
        Region spacer = new Region();
        HBox.setHgrow(spacer, javafx.scene.layout.Priority.ALWAYS);
        Button closeBtn = new Button("✕");
        closeBtn.setStyle("-fx-background-color: transparent; -fx-cursor: hand; -fx-font-size: 16px; -fx-text-fill: #9ca3af; -fx-padding: 0;");
        closeBtn.setOnAction(e -> stage.close());
        header.getChildren().addAll(titleLbl, spacer, closeBtn);
        
        Label msgLbl = new Label(message);
        msgLbl.setStyle("-fx-font-size: 14px; -fx-text-fill: #4b5563;");
        msgLbl.setWrapText(true);
        msgLbl.setMaxWidth(Double.MAX_VALUE);
        
        Button cancelBtn = new Button("Cancel");
        cancelBtn.setStyle("-fx-background-color: #f3f4f6; -fx-text-fill: #374151; -fx-border-radius: 6; -fx-background-radius: 6; -fx-padding: 8 16; -fx-cursor: hand; -fx-font-weight: bold;");
        cancelBtn.setOnAction(e -> stage.close());

        Button deleteBtn = new Button("Delete");
        deleteBtn.setStyle("-fx-background-color: #dc2626; -fx-text-fill: white; -fx-border-radius: 6; -fx-background-radius: 6; -fx-padding: 8 24; -fx-cursor: hand; -fx-font-weight: bold;");
        deleteBtn.setOnAction(e -> {
            stage.close();
            onConfirm.run();
        });
        
        HBox btnRow = new HBox(12, cancelBtn, deleteBtn);
        btnRow.setAlignment(javafx.geometry.Pos.CENTER_RIGHT);
        
        root.getChildren().addAll(header, msgLbl, btnRow);
        javafx.scene.Scene scene = new javafx.scene.Scene(root, javafx.scene.paint.Color.TRANSPARENT);
        stage.setScene(scene);
        stage.show();
    }

    private void onReportPost(Post p) {
        javafx.stage.Stage stage = new javafx.stage.Stage();
        stage.initStyle(javafx.stage.StageStyle.TRANSPARENT);
        stage.initModality(javafx.stage.Modality.APPLICATION_MODAL);
        
        VBox root = new VBox(16);
        root.setStyle("-fx-background-color: white; -fx-background-radius: 12; -fx-border-color: #e5e7eb; -fx-border-radius: 12; -fx-border-width: 1; -fx-padding: 24;");
        root.setAlignment(javafx.geometry.Pos.CENTER_LEFT);
        root.setPrefWidth(450);
        
        HBox header = new HBox();
        header.setAlignment(javafx.geometry.Pos.CENTER_LEFT);
        Label titleLbl = new Label("Report this post");
        titleLbl.setStyle("-fx-font-size: 18px; -fx-font-weight: bold; -fx-text-fill: #1f2937;");
        Region spacer = new Region();
        HBox.setHgrow(spacer, javafx.scene.layout.Priority.ALWAYS);
        Button closeBtn = new Button("✕");
        closeBtn.setStyle("-fx-background-color: transparent; -fx-cursor: hand; -fx-font-size: 18px; -fx-text-fill: #9ca3af; -fx-padding: 0;");
        closeBtn.setOnAction(e -> stage.close());
        header.getChildren().addAll(titleLbl, spacer, closeBtn);
        
        Label subtitleLbl = new Label("This sends a report to the system admin for review. It won't remove the post or notify its author.");
        subtitleLbl.setStyle("-fx-font-size: 14px; -fx-text-fill: #6b7280;");
        subtitleLbl.setWrapText(true);
        subtitleLbl.setMaxWidth(Double.MAX_VALUE);
        
        Label promptLbl = new Label("Reason");
        promptLbl.setStyle("-fx-font-size: 14px; -fx-font-weight: bold; -fx-text-fill: #374151;");
        
        javafx.scene.control.TextArea input = new javafx.scene.control.TextArea();
        input.setPromptText("Why are you reporting this post?");
        input.setPrefRowCount(3);
        input.setWrapText(true);
        input.setStyle("-fx-font-size: 14px; -fx-background-radius: 6; -fx-border-radius: 6; -fx-border-color: #d1d5db; -fx-background-color: white;");
        
        VBox inputGrp = new VBox(8, promptLbl, input);
        
        Button cancelBtn = new Button("Cancel");
        cancelBtn.setStyle("-fx-background-color: transparent; -fx-text-fill: #6b7280; -fx-font-size: 14px; -fx-cursor: hand;");
        cancelBtn.setOnAction(e -> stage.close());

        Button submitBtn = new Button("Submit Report");
        submitBtn.setStyle("-fx-background-color: #dc2626; -fx-text-fill: white; -fx-border-radius: 6; -fx-background-radius: 6; -fx-padding: 8 16; -fx-cursor: hand; -fx-font-weight: bold; -fx-font-size: 14px;");
        submitBtn.setOnAction(e -> {
            String reason = input.getText();
            if (reason == null || reason.isBlank()) return;
            stage.close();
            Thread worker = new Thread(() -> {
                try {
                    String token = Session.authToken();
                    if (token != null) {
                        api.reportPost(token, p.getPostId(), reason);
                        Platform.runLater(() -> showThemedWarning("Success", "Report submitted."));
                    }
                } catch (Exception ex) {
                    Platform.runLater(() -> showThemedWarning("Error", "Failed to report: " + ex.getMessage()));
                }
            });
            worker.setDaemon(true);
            worker.start();
        });
        
        HBox btnRow = new HBox(16, cancelBtn, submitBtn);
        btnRow.setAlignment(javafx.geometry.Pos.CENTER_RIGHT);
        
        root.getChildren().addAll(header, subtitleLbl, inputGrp, btnRow);
        javafx.scene.Scene scene = new javafx.scene.Scene(root, javafx.scene.paint.Color.TRANSPARENT);
        stage.setScene(scene);
        stage.show();
    }

    private void onEditPost(Post p) {
        showThemedInput("Edit Post", "Edit your message:", p.getContent(), newContent -> {
            if (newContent.isBlank() || newContent.equals(p.getContent())) return;
            Thread worker = new Thread(() -> {
                try {
                    String token = Session.authToken();
                    if (token != null && p.isSynced()) {
                        forum.api.dto.PostDto updated = api.updatePost(token, p.getPostId(), newContent);
                        postDao.upsertFromServer(updated);
                    } else {
                        postDao.updateContentLocally(p.getPostId(), newContent);
                    }
                    List<Post> fresh = postDao.listByTopic(topic.getTopicId());
                    Platform.runLater(() -> renderPosts(fresh));
                } catch (Exception e) {
                    Platform.runLater(() -> showThemedWarning("Error", "Failed to edit: " + e.getMessage()));
                }
            });
            worker.setDaemon(true);
            worker.start();
        });
    }

    private void onDeletePost(Post p) {
        showThemedConfirm("Delete Post", "Are you sure you want to delete this message forever?", () -> {
            Thread worker = new Thread(() -> {
                try {
                    String token = Session.authToken();
                    if (token != null && p.isSynced()) {
                        api.deletePost(token, p.getPostId());
                    }
                    postDao.deleteLocally(p.getPostId());
                    List<Post> fresh = postDao.listByTopic(topic.getTopicId());
                    Platform.runLater(() -> renderPosts(fresh));
                } catch (Exception e) {
                    Platform.runLater(() -> showThemedWarning("Error", "Failed to delete: " + e.getMessage()));
                }
            });
            worker.setDaemon(true);
            worker.start();
        });
    }

    private String formatTime(String timeStr) {
        if (timeStr == null || timeStr.isBlank()) return "pending";
        try {
            if (timeStr.contains("T")) {
                java.time.OffsetDateTime odt = java.time.OffsetDateTime.parse(timeStr);
                return odt.atZoneSameInstant(java.time.ZoneId.systemDefault())
                          .format(java.time.format.DateTimeFormatter.ofPattern("h:mm a"));
            } else {
                java.time.LocalDateTime ldt = java.time.LocalDateTime.parse(timeStr, java.time.format.DateTimeFormatter.ofPattern("yyyy-MM-dd HH:mm:ss"));
                return ldt.format(java.time.format.DateTimeFormatter.ofPattern("h:mm a"));
            }
        } catch (Exception e) {
            try {
                java.time.Instant instant = java.time.Instant.parse(timeStr);
                return java.time.ZonedDateTime.ofInstant(instant, java.time.ZoneId.systemDefault())
                           .format(java.time.format.DateTimeFormatter.ofPattern("h:mm a"));
            } catch (Exception ex) {
                return timeStr;
            }
        }
    }

    private void openWebLink(String url) {
        try {
            java.awt.Desktop.getDesktop().browse(new java.net.URI(url));
        } catch (Exception ex) {
            System.err.println("Failed to open web link: " + ex.getMessage());
        }
    }
}
