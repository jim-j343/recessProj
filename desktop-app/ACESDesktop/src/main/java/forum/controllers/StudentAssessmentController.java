package forum.controllers;

import forum.api.ApiClient;
import forum.api.ApiException;
import forum.api.dto.StudentProgressDto;
import forum.app.SceneManager;
import forum.app.Session;
import forum.models.User;
import forum.util.NavbarHelper;

import javafx.application.Platform;
import javafx.fxml.FXML;
import javafx.geometry.Insets;
import javafx.scene.control.Label;
import javafx.scene.control.MenuButton;
import javafx.scene.control.ProgressBar;
import javafx.scene.layout.HBox;
import javafx.scene.layout.Region;
import javafx.scene.layout.VBox;

public class StudentAssessmentController {

    @FXML private Label      avatarLabel;
    @FXML private Label      userNameLabel;
    @FXML private MenuButton notifButton;
    @FXML private Label      notifBadge;

    @FXML private HBox        activityChartBox;
    @FXML private HBox        activityLabelsBox;
    @FXML private Label       participationPctLabel;
    @FXML private ProgressBar participationProgressBar;
    @FXML private Label       participationSubLabel;

    @FXML private VBox  assessmentHistoryBox;
    @FXML private Label assessmentEmptyLabel;

    @FXML private VBox  remarkBox;
    @FXML private Label remarkQuoteLabel;
    @FXML private Label remarkMetaLabel;
    @FXML private VBox  noRemarkBox;

    private final ApiClient api = new ApiClient();

    @FXML
    private void initialize() {
        User u = Session.currentUser();
        if (u != null) {
            if (avatarLabel != null) {
                String name = u.displayName();
                avatarLabel.setText(name == null || name.isBlank() ? "?" : String.valueOf(name.trim().charAt(0)).toUpperCase());
            }
            if (userNameLabel != null)
                userNameLabel.setText(u.displayName());
        }
        NavbarHelper.loadNotifications(api, notifButton, notifBadge);
        loadProgress();
    }

    private void loadProgress() {
        String token = Session.authToken();
        if (token == null) return;

        Thread worker = new Thread(() -> {
            try {
                StudentProgressDto dto = api.studentProgress(token);
                Platform.runLater(() -> render(dto));
            } catch (ApiException | java.io.IOException | InterruptedException e) {
                if (e instanceof InterruptedException) Thread.currentThread().interrupt();
            }
        }, "student-progress-load");
        worker.setDaemon(true);
        worker.start();
    }

    private void render(StudentProgressDto dto) {
        renderActivityChart(dto);

        int pct = (int) Math.round(dto.participationPct);
        participationPctLabel.setText(pct + "%");
        participationProgressBar.setProgress(pct / 100.0);
        int countedReplies = Math.min(dto.replyCount, 10);
        participationSubLabel.setText(dto.replyCount + (dto.replyCount == 1 ? " reply" : " replies")
                + " out of " + dto.postCount + " total posts ("
                + countedReplies + "/10 replies counted — 10 or more reaches 100%)");

        renderAssessmentHistory(dto);
        renderRemark(dto);
    }

    private void renderActivityChart(StudentProgressDto dto) {
        activityChartBox.getChildren().clear();
        activityLabelsBox.getChildren().clear();
        if (dto.activityByDay == null || dto.activityByDay.isEmpty()) return;

        int peak = 1;
        for (var day : dto.activityByDay) peak = Math.max(peak, day.count);

        for (var day : dto.activityByDay) {
            double heightPct = Math.max(0.06, (double) day.count / peak);

            Region bar = new Region();
            bar.getStyleClass().add("bar");
            bar.getStyleClass().add(day.count > 0 ? "bar-hi" : "bar-dim");
            bar.setPrefHeight(96 * heightPct);
            bar.setMaxWidth(Double.MAX_VALUE);

            VBox col = new VBox(bar);
            col.setAlignment(javafx.geometry.Pos.BOTTOM_CENTER);
            HBox.setHgrow(col, javafx.scene.layout.Priority.ALWAYS);
            activityChartBox.getChildren().add(col);

            Label label = new Label(day.label);
            label.getStyleClass().add("subtle");
            label.setMaxWidth(Double.MAX_VALUE);
            label.setAlignment(javafx.geometry.Pos.CENTER);
            HBox.setHgrow(label, javafx.scene.layout.Priority.ALWAYS);
            activityLabelsBox.getChildren().add(label);
        }
    }

    private void renderAssessmentHistory(StudentProgressDto dto) {
        assessmentHistoryBox.getChildren().clear();
        boolean empty = dto.assessmentHistory == null || dto.assessmentHistory.isEmpty();
        assessmentEmptyLabel.setManaged(empty);
        assessmentEmptyLabel.setVisible(empty);
        if (empty) return;

        for (var item : dto.assessmentHistory) {
            Label title = new Label(item.title);
            title.getStyleClass().add("label-strong");
            Label completed = new Label("Completed " + item.submittedAtHuman);
            completed.getStyleClass().add("subtle");
            VBox left = new VBox(2, title, completed);

            Label score = new Label(item.scorePct + "%");
            score.getStyleClass().add("label-strong");

            Region spacer = new Region();
            HBox.setHgrow(spacer, javafx.scene.layout.Priority.ALWAYS);

            HBox row = new HBox(left, spacer, score);
            row.setAlignment(javafx.geometry.Pos.CENTER_LEFT);

            if (item.vsPeerPct != null) {
                boolean positive = item.vsPeerPct >= 0;
                Label vsPeer = new Label((positive ? "+" : "") + item.vsPeerPct + "%");
                vsPeer.getStyleClass().add(positive ? "badge-success" : "badge-danger");
                vsPeer.getStyleClass().add("badge");
                row.getChildren().add(vsPeer);
            }

            VBox wrapper = new VBox(row);
            wrapper.setPadding(new Insets(10, 0, 10, 0));

            if (!assessmentHistoryBox.getChildren().isEmpty()) {
                Region div = new Region();
                div.getStyleClass().add("divider");
                div.setPrefHeight(1);
                assessmentHistoryBox.getChildren().add(div);
            }
            assessmentHistoryBox.getChildren().add(wrapper);
        }
    }

    private void renderRemark(StudentProgressDto dto) {
        boolean has = dto.latestRemark != null;
        remarkBox.setManaged(has);
        remarkBox.setVisible(has);
        noRemarkBox.setManaged(!has);
        noRemarkBox.setVisible(!has);

        if (has) {
            remarkQuoteLabel.setText("\"" + dto.latestRemark.criteria + "\"");
            remarkMetaLabel.setText("Score awarded: " + dto.latestRemark.score + " · " + dto.latestRemark.createdAtHuman);
        }
    }

    @FXML private void onDashboard() {
        User u = Session.currentUser();
        if (u != null) SceneManager.showHomeFor(u.getRole());
    }
    @FXML private void onGroups()  { SceneManager.goGroups(); }
    @FXML private void onForum()   { SceneManager.goForumDashboard(); }
    @FXML private void onProfile() { SceneManager.goProfile(); }
    @FXML private void onLogout()  {
        String token = Session.authToken();
        Session.end();
        new Thread(() -> new forum.services.AuthService().logout(token), "logout").start();
        SceneManager.show("Login", "ACES");
    }
}