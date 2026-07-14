package forum.controllers;

import forum.api.ApiClient;
import forum.app.SceneManager;
import forum.app.Session;
import forum.app.ViewState;
import forum.database.TopicDao;
import forum.models.Topic;
import forum.models.User;
import forum.services.AuthService;
import forum.services.SyncService;
import forum.util.NavbarHelper;
import javafx.scene.control.MenuButton;

import javafx.application.Platform;
import javafx.fxml.FXML;
import javafx.geometry.Insets;
import javafx.scene.control.Label;
import javafx.scene.control.TextField;
import javafx.scene.layout.HBox;
import javafx.scene.layout.Priority;
import javafx.scene.layout.Region;
import javafx.scene.layout.VBox;

import java.util.List;

public class ForumDashboardController {

    @FXML private Label avatarLabel;
    @FXML private Label userNameLabel;
    
    @FXML private VBox myGroupsBox;
    @FXML private TextField searchField;
    @FXML private VBox discussionList;
    @FXML private VBox unansweredBox;

    private final TopicDao topicDao = new TopicDao();
    private final ApiClient api = new ApiClient();

    @FXML
    private void initialize() {
        User u = Session.currentUser();
        if (u != null) {
            if (avatarLabel != null) avatarLabel.setText(initial(u.displayName()));
            if (userNameLabel != null) userNameLabel.setText(u.displayName());
        }

        renderTopics(topicDao.listRecent(15));
        fetchMyGroups();
        syncInBackground();
    }

    private void syncInBackground() {
        Thread worker = new Thread(() -> {
            new SyncService().syncNow();
            List<Topic> fresh = topicDao.listRecent(15);
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

        for (Topic t : topics) {
            VBox card = new VBox(8);
            card.getStyleClass().add("card-flat");
            card.setStyle("-fx-border-color: #e5e7eb; -fx-border-width: 1; -fx-border-radius: 8; -fx-padding: 16; -fx-cursor: hand;");
            
            HBox header = new HBox(12);
            Label av = new Label(initial(t.getAuthorName()));
            av.getStyleClass().addAll("avatar", "avatar-soft");
            av.setMinSize(36, 36);

            VBox text = new VBox(2);
            Label title = new Label(t.getTitle());
            title.getStyleClass().add("label-strong");
            title.setWrapText(true);

            text.getChildren().addAll(title);

            HBox.setHgrow(text, Priority.ALWAYS);
            header.getChildren().addAll(av, text);

            HBox footer = new HBox(8);
            if (t.getCategory() != null && !t.getCategory().isBlank()) {
                Label tag = new Label("#" + t.getCategory());
                tag.setStyle("-fx-text-fill: #4f46e5; -fx-font-size: 11px; -fx-font-weight: bold;");
                footer.getChildren().add(tag);
            }
            Region spacer = new Region();
            HBox.setHgrow(spacer, Priority.ALWAYS);
            
            Label meta = new Label("Posted by " + safe(t.getAuthorName()) + "  ·  💬 " + t.getReplyCount());
            meta.getStyleClass().add("subtle");
            meta.setStyle("-fx-font-size: 11px;");
            
            footer.getChildren().addAll(spacer, meta);

            card.getChildren().addAll(header, footer);
            card.setOnMouseClicked(e -> openTopic(t));

            discussionList.getChildren().add(card);
        }
    }

    private void fetchMyGroups() {
        String token = Session.authToken();
        if (token == null) return;
        Thread worker = new Thread(() -> {
            try {
                List<forum.api.dto.GroupDto> groups = new ApiClient().listGroups(token);
                Platform.runLater(() -> renderSidebarGroups(groups));
            } catch (Exception e) {
                if (e instanceof InterruptedException) Thread.currentThread().interrupt();
            }
        }, "fetch-my-groups");
        worker.setDaemon(true);
        worker.start();
    }

    private void renderSidebarGroups(List<forum.api.dto.GroupDto> groups) {
        if (myGroupsBox != null) {
            myGroupsBox.getChildren().clear();
            if (groups == null || groups.isEmpty()) {
                Label l = new Label("No active groups");
                l.getStyleClass().add("muted");
                myGroupsBox.getChildren().add(l);
            } else {
                for (forum.api.dto.GroupDto g : groups) {
                    if ("active".equals(g.myStatus) || "admin".equals(g.myRole)) {
                        Label gl = groupLink("📁 " + g.name);
                        gl.setOnMouseClicked(e -> {
                            ViewState.setSelectedGroup(g);
                            SceneManager.goGroupShow();
                        });
                        myGroupsBox.getChildren().add(gl);
                    }
                }
            }
        }

        if (unansweredBox != null) {
            unansweredBox.getChildren().clear();
            unansweredBox.getChildren().add(unansweredLink("How to handle race conditions in distributed systems?", "URGENT", "8m ago"));
            unansweredBox.getChildren().add(unansweredLink("WebAssembly vs JS for high-perf computation?", null, "Trending · 2h ago"));
        }
    }

    private Label groupLink(String text) {
        Label l = new Label(text);
        l.setStyle("-fx-text-fill: #2563eb; -fx-font-size: 13px; -fx-padding: 4 8; -fx-cursor: hand;");
        return l;
    }

    private VBox unansweredLink(String title, String badge, String time) {
        VBox box = new VBox(4);
        box.setStyle("-fx-border-color: transparent transparent #f3f4f6 transparent; -fx-border-width: 1; -fx-padding: 0 0 8 0;");
        Label t = new Label(title);
        t.setStyle("-fx-font-size: 12px; -fx-font-weight: 500; -fx-text-fill: #1f2937;");
        t.setWrapText(true);
        
        HBox meta = new HBox(8);
        if (badge != null) {
            Label b = new Label(badge);
            b.getStyleClass().addAll("badge", "badge-danger");
            b.setStyle("-fx-font-size: 9px; -fx-padding: 2 4;");
            meta.getChildren().add(b);
        }
        Label timeL = new Label(time);
        timeL.setStyle("-fx-font-size: 11px; -fx-text-fill: #9ca3af;");
        meta.getChildren().add(timeL);

        box.getChildren().addAll(t, meta);
        return box;
    }

    private void openTopic(Topic t) {
        ViewState.setSelectedTopic(t);
        SceneManager.show("TopicDetail", "Smart Discussion Forum — " + t.getTitle());
    }

    @FXML private void onDashboard() {
        User u = Session.currentUser();
        if (u != null) SceneManager.showHomeFor(u.getRole());
    }
    @FXML private void onGroups() { SceneManager.goGroups(); }
    @FXML private void onNewThread() { SceneManager.show("TopicCreation", "Smart Discussion Forum — New Topic"); }
    @FXML private void onProfile() { SceneManager.goProfile(); }

    @FXML private void onLogout() {
        String token = Session.authToken();
        Session.end();
        Thread t = new Thread(() -> new AuthService().logout(token), "aces-logout");
        t.setDaemon(true);
        t.start();
        SceneManager.show("Login", "Smart Discussion Forum");
    }

    private String safe(String s) { return s == null ? "Unknown" : s; }

    /** Single first-letter initial — matches web x-avatar component. */
    private String initial(String name) {
        if (name == null || name.isBlank()) return "?";
        return String.valueOf(name.trim().charAt(0)).toUpperCase();
    }
}
