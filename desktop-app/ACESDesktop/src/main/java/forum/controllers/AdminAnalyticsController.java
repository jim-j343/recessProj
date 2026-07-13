package forum.controllers;

import forum.api.ApiClient;
import forum.api.dto.AdminAnalyticsDto;
import forum.app.SceneManager;
import forum.app.Session;
import forum.models.User;
import forum.services.AuthService;
import forum.util.NavbarHelper;

import javafx.application.Platform;
import javafx.fxml.FXML;
import javafx.geometry.Insets;
import javafx.geometry.Pos;
import javafx.scene.control.Label;
import javafx.scene.control.MenuButton;
import javafx.scene.control.ProgressBar;
import javafx.scene.layout.HBox;
import javafx.scene.layout.Priority;
import javafx.scene.layout.Region;
import javafx.scene.layout.VBox;
import javafx.scene.layout.FlowPane;

public class AdminAnalyticsController {

    @FXML private Label      avatarLabel;
    @FXML private Label      userNameLabel;
    @FXML private MenuButton notifButton;
    @FXML private Label      notifBadge;
    @FXML private Label      totalMembersLabel;
    @FXML private Label      activeThisWeekLabel;
    @FXML private Label      activeThisWeekMetaLabel;
    @FXML private Label      warningsThisWeekLabel;
    @FXML private Label      activeBlacklistsLabel;
    @FXML private Label      statusLabel;
    
    @FXML private VBox postVolumeBox;
    @FXML private VBox groupPerformanceBox;
    @FXML private VBox groupActivityBox;
    @FXML private FlowPane groupsBox;
    @FXML private VBox recentActivityBox;

    private final ApiClient api = new ApiClient();

    @FXML
    private void initialize() {
        User user = Session.currentUser();
        if (user != null) {
            userNameLabel.setText(user.displayName());
            avatarLabel.setText(initial(user.displayName()));
        }
        NavbarHelper.loadNotifications(api, notifButton, notifBadge);
        loadAnalytics();
    }

    private void loadAnalytics() {
        String token = Session.authToken();
        if (token == null || token.isBlank()) {
            showStatus("Admin analytics requires an online web-app session.");
            return;
        }

        Thread worker = new Thread(() -> {
            try {
                AdminAnalyticsDto data = api.adminAnalytics(token);
                Platform.runLater(() -> render(data));
            } catch (Exception e) {
                if (e instanceof InterruptedException) Thread.currentThread().interrupt();
                Platform.runLater(() -> showStatus("Could not load analytics: " + e.getMessage()));
            }
        }, "admin-analytics-load");
        worker.setDaemon(true);
        worker.start();
    }

    private void render(AdminAnalyticsDto data) {
        totalMembersLabel.setText(String.valueOf(data.totalMembers));
        activeThisWeekLabel.setText(String.valueOf(data.activeThisWeek));
        warningsThisWeekLabel.setText(String.valueOf(data.warningsThisWeek));
        activeBlacklistsLabel.setText(String.valueOf(data.activeBlacklists));

        double pct = data.totalMembers > 0 ? (data.activeThisWeek * 100.0) / data.totalMembers : 0;
        activeThisWeekMetaLabel.setText(String.format("%.1f%% of total members", pct));

        renderPostVolume(data);
        renderPerformance(data);
        renderActivity(data);
        renderGroups(data);
        renderRecentActivity(data);
        showStatus("Loaded from web backend.");
    }

    private void renderPostVolume(AdminAnalyticsDto data) {
        postVolumeBox.getChildren().clear();
        if (data.postVolume == null || data.postVolume.isEmpty()) {
            postVolumeBox.getChildren().add(muted("No post activity yet."));
            return;
        }
        int peak = data.postVolume.stream().mapToInt(p -> p.count).max().orElse(1);
        HBox bars = new HBox(12);
        bars.setPrefHeight(180);
        bars.setMinHeight(180);
        for (AdminAnalyticsDto.CountPoint point : data.postVolume) {
            double height = Math.max(8, (point.count / (double) Math.max(1, peak)) * 150);
            Region bar = new Region();
            bar.getStyleClass().add("bar");
            bar.setPrefHeight(height);
            bar.setMaxWidth(Double.MAX_VALUE);
            VBox item = new VBox(4, muted(String.valueOf(point.count)), bar, muted(point.label));
            item.setFillWidth(true);
            item.setStyle("-fx-alignment: bottom-center;");
            HBox.setHgrow(item, Priority.ALWAYS);
            bars.getChildren().add(item);
        }
        postVolumeBox.getChildren().add(bars);
    }

    private void renderPerformance(AdminAnalyticsDto data) {
        groupPerformanceBox.getChildren().clear();
        if (data.groupPerformance == null || data.groupPerformance.isEmpty()) {
            groupPerformanceBox.getChildren().add(muted("No completed quizzes yet."));
            return;
        }
        for (AdminAnalyticsDto.GroupPerformance row : data.groupPerformance) {
            HBox labels = new HBox();
            Label name = new Label(row.name);
            name.setStyle("-fx-font-size: 12px; -fx-font-weight: 500; -fx-text-fill: #4b5563;");
            Region spacer = new Region();
            HBox.setHgrow(spacer, Priority.ALWAYS);
            Label pct = new Label(String.format("%.1f%%", row.avgPct == null ? 0 : row.avgPct));
            pct.setStyle("-fx-font-size: 12px; -fx-font-weight: bold; -fx-text-fill: #111827;");
            labels.getChildren().addAll(name, spacer, pct);

            ProgressBar bar = new ProgressBar((row.avgPct == null ? 0 : row.avgPct) / 100.0);
            bar.setMaxWidth(Double.MAX_VALUE);
            bar.setStyle("-fx-accent: #111827;");

            Label count = new Label(row.count + " completed quizzes");
            count.setStyle("-fx-font-size: 11px; -fx-text-fill: #9ca3af;");

            VBox item = new VBox(4, labels, bar, count);
            VBox.setMargin(item, new Insets(0, 0, 8, 0));
            groupPerformanceBox.getChildren().add(item);
        }
    }

    private void renderActivity(AdminAnalyticsDto data) {
        groupActivityBox.getChildren().clear();
        if (data.groupActivity == null || data.groupActivity.isEmpty()) {
            groupActivityBox.getChildren().add(muted("No group activity yet."));
            return;
        }
        int peak = data.groupActivity.stream().mapToInt(p -> p.count).max().orElse(1);
        for (AdminAnalyticsDto.CountPoint row : data.groupActivity) {
            HBox labels = new HBox();
            Label name = new Label(row.name);
            name.setStyle("-fx-font-size: 12px; -fx-font-weight: 500; -fx-text-fill: #4b5563;");
            Region spacer = new Region();
            HBox.setHgrow(spacer, Priority.ALWAYS);
            Label count = new Label(row.count + " posts");
            count.setStyle("-fx-font-size: 12px; -fx-font-weight: bold; -fx-text-fill: #111827;");
            labels.getChildren().addAll(name, spacer, count);

            ProgressBar bar = new ProgressBar((double) row.count / Math.max(1, peak));
            bar.setMaxWidth(Double.MAX_VALUE);
            bar.setStyle("-fx-accent: #4f46e5;");

            VBox item = new VBox(4, labels, bar);
            VBox.setMargin(item, new Insets(0, 0, 8, 0));
            groupActivityBox.getChildren().add(item);
        }
    }

    private void renderGroups(AdminAnalyticsDto data) {
        groupsBox.getChildren().clear();
        if (data.groups == null || data.groups.isEmpty()) {
            groupsBox.getChildren().add(muted("No groups yet."));
            return;
        }
        for (AdminAnalyticsDto.GroupSummary group : data.groups) {
            Label label = new Label(group.name + " · " + group.topicsCount + " topics");
            label.getStyleClass().add(group.topicsCount > 0 ? "chip" : "chip-outline");
            if (group.topicsCount > 0) {
                label.setStyle("-fx-background-color:#111827; -fx-text-fill:white;");
            }
            groupsBox.getChildren().add(label);
        }
    }

    private void renderRecentActivity(AdminAnalyticsDto data) {
        recentActivityBox.getChildren().clear();
        if (data.recentActivity == null || data.recentActivity.isEmpty()) {
            recentActivityBox.getChildren().add(muted("No activity logged yet."));
            return;
        }
        for (int i = 0; i < data.recentActivity.size(); i++) {
            AdminAnalyticsDto.ActivityItem item = data.recentActivity.get(i);
            String group = item.group == null || item.group.isBlank() ? "" : " in " + item.group;
            
            Label av = new Label(initial(item.user));
            av.getStyleClass().add("avatar-soft");
            av.setMinSize(32, 32);

            Label txt = new Label((item.user == null ? "Unknown" : item.user) + " " + item.action + group);
            txt.getStyleClass().add("label-strong");
            txt.setWrapText(true);

            Label time = new Label(item.loggedAtHuman == null ? "" : item.loggedAtHuman);
            time.getStyleClass().add("subtle");

            VBox textCol = new VBox(2, txt, time);
            HBox row = new HBox(12, av, textCol);
            row.setAlignment(Pos.TOP_LEFT);
            
            recentActivityBox.getChildren().add(row);
            
            if (i < data.recentActivity.size() - 1) {
                Region divider = new Region();
                divider.getStyleClass().add("divider");
                divider.setPrefHeight(1);
                VBox.setMargin(divider, new Insets(8, 0, 8, 0));
                recentActivityBox.getChildren().add(divider);
            }
        }
    }

    @FXML private void onDashboard() { SceneManager.goAdminDashboard(); }
    @FXML private void onGroups()    { SceneManager.goGroups(); }
    @FXML private void onMembers()   { SceneManager.goAdminMembers(); }
    @FXML private void onAnalytics() { SceneManager.goAdminAnalytics(); }

    @FXML private void onProfile() { forum.app.SceneManager.goProfile(); }

    @FXML
    private void onLogout() {
        String token = Session.authToken();
        Session.end();
        new Thread(() -> new AuthService().logout(token), "logout").start();
        SceneManager.show("Login", "Smart Discussion Forum");
    }

    private Label muted(String text) {
        Label label = new Label(text == null ? "" : text);
        label.getStyleClass().add("muted");
        label.setWrapText(true);
        return label;
    }

    private String initial(String name) {
        if (name == null || name.isBlank()) return "?";
        return String.valueOf(name.trim().charAt(0)).toUpperCase();
    }

    private void showStatus(String message) {
        if (statusLabel == null) return;
        statusLabel.setText(message);
        statusLabel.setManaged(true);
        statusLabel.setVisible(true);
    }
}
