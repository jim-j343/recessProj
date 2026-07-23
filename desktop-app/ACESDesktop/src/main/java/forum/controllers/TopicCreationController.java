package forum.controllers;

import forum.api.ApiClient;
import forum.api.ApiException;
import forum.api.dto.TopicDto;
import forum.app.SceneManager;
import forum.app.Session;
import forum.app.ViewState;
import forum.database.PostDao;
import forum.database.TopicDao;
import forum.models.Topic;
import forum.models.User;
import forum.util.NavbarHelper;

import javafx.application.Platform;
import javafx.fxml.FXML;
import javafx.scene.control.ComboBox;
import javafx.scene.control.Label;
import javafx.scene.control.TextArea;
import javafx.scene.control.TextField;

/**
 * Publishes a topic. Online: POST to the API and cache the result.
 * Offline: create locally (queued) with a local-only first post for display.
 * Runs the network call off the UI thread.
 */
public class TopicCreationController {

    @FXML private TextField titleField;
    @FXML private ComboBox<String> categoryCombo;
    @FXML private ComboBox<forum.api.dto.GroupDto> groupCombo;
    @FXML private TextArea descriptionArea;
    @FXML private Label errorLabel;
    @FXML private Label userNameLabel;
    @FXML private Label avatarLabel;
    @FXML private javafx.scene.control.MenuButton notifButton;
    @FXML private Label notifBadge;
    @FXML private Label navMyProgress;
    @FXML private Label navQuizCenter;
    @FXML private Label navGrading;


    private final TopicDao topicDao = new TopicDao();
    private final PostDao postDao = new PostDao();
    private final ApiClient api = new ApiClient();

    @FXML
    private void initialize() {
        User u = Session.currentUser();
        if (u != null) {
            NavbarHelper.applyRoleNav(u.getRole(), navMyProgress, null, navQuizCenter, navGrading, null, null, null);
        }
        if (u != null) {
            if (userNameLabel != null) userNameLabel.setText(u.displayName());
            if (avatarLabel != null) {
                String name = u.displayName();
                avatarLabel.setText(name == null || name.isBlank() ? "?" : String.valueOf(name.trim().charAt(0)).toUpperCase());
            }
        }
        if (notifButton != null) {
            forum.util.NavbarHelper.loadNotifications(api, notifButton, notifBadge);
        }
        if (errorLabel != null) errorLabel.setManaged(false);
        if (categoryCombo != null && categoryCombo.getItems().isEmpty()) {
            categoryCombo.getItems().addAll("General", "Programming", "Mathematics", "Science", "Announcements");
        }
        
        if (groupCombo != null) {
            groupCombo.setConverter(new javafx.util.StringConverter<>() {
                @Override public String toString(forum.api.dto.GroupDto g) { return g == null ? "" : g.name; }
                @Override public forum.api.dto.GroupDto fromString(String string) { return null; }
            });
            loadGroups();
        }
    }

    private void loadGroups() {
        String token = Session.authToken();
        if (token == null) return;
        Thread t = new Thread(() -> {
            try {
                java.util.List<forum.api.dto.GroupDto> groups = api.listGroups(token);
                Platform.runLater(() -> {
                    groupCombo.getItems().setAll(groups);
                    if (!groups.isEmpty()) groupCombo.getSelectionModel().selectFirst();
                });
            } catch (Exception e) {}
        });
        t.setDaemon(true);
        t.start();
    }

    @FXML
    private void onPublish() {
        clearError();
        String title = titleField == null ? "" : titleField.getText();
        if (title == null || title.isBlank()) { showError("Please enter a topic title."); return; }
        
        forum.api.dto.GroupDto selectedGroup = groupCombo != null ? groupCombo.getValue() : null;
        if (selectedGroup == null) { showError("Please select a group."); return; }
        
        long groupId = selectedGroup.groupId;
        
        String content = descriptionArea == null ? "" : descriptionArea.getText();
        if (content == null || content.isBlank()) { showError("Please enter a description."); return; }
        String category = (categoryCombo != null && categoryCombo.getValue() != null)
                ? categoryCombo.getValue() : "General";

        User u = Session.currentUser();
        long creatorId = (u != null) ? u.getUserId() : 0;
        String token = Session.authToken();

        Thread worker = new Thread(() -> {
            // 1. Online — publish to the server.
            if (token != null && !token.isBlank()) {
                try {
                    TopicDto dto = api.createTopic(token, groupId, title.trim(), category, content.trim());
                    topicDao.upsertFromServer(dto);
                    Topic cached = topicDao.findById(dto.topic_id);
                    Topic result = (cached != null) ? cached : fromDto(dto);
                    Platform.runLater(() -> openDetail(result));
                    return;
                } catch (ApiException rejected) {
                    Platform.runLater(() -> showError(rejected.getMessage()));
                    return;
                } catch (java.io.IOException | InterruptedException offline) {
                    if (offline instanceof InterruptedException) Thread.currentThread().interrupt();
                    // fall through to offline creation
                }
            }
            // 2. Offline — create locally, queued for sync.
            Topic t = topicDao.create(groupId, creatorId, title.trim(), category);
            if (t == null) { Platform.runLater(() -> showError("Could not publish the topic.")); return; }
            postDao.createLocalOnly(t.getTopicId(), creatorId, null, content.trim());
            Platform.runLater(() -> openDetail(t));
        }, "aces-create-topic");
        worker.setDaemon(true);
        worker.start();
    }

    private void openDetail(Topic t) {
        ViewState.setSelectedTopic(t);
        SceneManager.show("TopicDetail", "ACES — " + t.getTitle());
    }

    private Topic fromDto(TopicDto dto) {
        Topic t = new Topic();
        t.setTopicId(dto.topic_id);
        t.setGroupId(dto.group_id);
        t.setCreatorId(dto.creator_id);
        t.setTitle(dto.title);
        t.setCategory(dto.category);
        t.setAuthorName(dto.author);
        t.setReplyCount(dto.replies);
        return t;
    }

    @FXML private void onCancel() { SceneManager.goForumDashboard(); }
    @FXML private void onDashboard() {
        User u = Session.currentUser();
        if (u != null) SceneManager.showHomeFor(u.getRole());
    }
    @FXML private void onGroups() { SceneManager.goGroups(); }
    @FXML private void onMyProgress() { SceneManager.goStudentAssessment(); }
    @FXML private void onQuizCenter() { SceneManager.goQuizManagement(); }
    @FXML private void onGrading() { SceneManager.goParticipationGrading(); }
    @FXML private void onProfile()   { SceneManager.goProfile(); }
    @FXML private void onLogout()    {
        String token = forum.app.Session.authToken();
        forum.app.Session.end();
        new Thread(() -> new forum.services.AuthService().logout(token)).start();
        SceneManager.show("Login", "ACES");
    }

    private void showError(String msg) {
        if (errorLabel == null) return;
        errorLabel.setText(msg);
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
