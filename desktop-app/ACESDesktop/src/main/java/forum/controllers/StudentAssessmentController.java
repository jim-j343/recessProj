package forum.controllers;

import forum.api.ApiClient;
import forum.api.dto.StudentProgressDto;
import forum.app.SceneManager;
import forum.app.Session;
import forum.models.Role;
import forum.models.User;
import forum.util.NavbarHelper;

import javafx.application.Platform;
import javafx.fxml.FXML;
import javafx.geometry.Pos;
import javafx.scene.control.Label;
import javafx.scene.control.MenuButton;
import javafx.scene.layout.HBox;
import javafx.scene.layout.Priority;
import javafx.scene.layout.Region;
import javafx.scene.layout.StackPane;
import javafx.scene.layout.VBox;

import java.util.List;

public class StudentAssessmentController {

    @FXML private Label      avatarLabel;
    @FXML private Label      userNameLabel;
    @FXML private MenuButton notifButton;
    @FXML private Label      notifBadge;
    @FXML private Label      navNewTopic;

    // ── Participation card ────────────────────────────────────────────
    @FXML private HBox    activityChartBox;
    @FXML private HBox    activityLabelsBox;
    @FXML private Label   participationPctLabel;
    @FXML private StackPane participationBarPane;
    @FXML private Region  participationBarFill;
    @FXML private Label   participationSubLabel;

    // ── Assessment history ────────────────────────────────────────────
    @FXML private VBox    assessmentHistoryBox;

    // ── Lecturer's remark ─────────────────────────────────────────────
    @FXML private Label   remarkLabel;
    @FXML private Label   remarkMetaLabel;

    private final ApiClient api = new ApiClient();

    @FXML
    private void initialize() {
        User u = Session.currentUser();
        if (u != null) {
            if (avatarLabel != null) {
                String name = u.displayName();
                avatarLabel.setText(name == null || name.isBlank() ? "?" :
                    String.valueOf(name.trim().charAt(0)).toUpperCase());
            }
            if (userNameLabel != null) userNameLabel.setText(u.displayName());
            if (u.getRole() != Role.SYSTEM_ADMIN && navNewTopic != null) {
                navNewTopic.setManaged(true);
                navNewTopic.setVisible(true);
            }
        }
        NavbarHelper.loadNotifications(api, notifButton, notifBadge);
        loadProgress();
    }

    private void loadProgress() {
        String token = Session.authToken();
        if (token == null) return;
        new Thread(() -> {
            try {
                StudentProgressDto dto = api.studentProgress(token);
                Platform.runLater(() -> render(dto));
            } catch (Exception e) {
                if (e instanceof InterruptedException) Thread.currentThread().interrupt();
            }
        }, "assessment-load").start();
    }

    private void render(StudentProgressDto dto) {
        // ── 1. Activity bar chart (last 7 days) ─────────────────────
        renderActivityChart(dto.activityByDay);

        // ── 2. Participation bar ─────────────────────────────────────
        double pct = dto.participationPct;
        if (participationPctLabel != null)
            participationPctLabel.setText(String.format("%.0f%%", pct));

        if (participationBarFill != null && participationBarPane != null) {
            final double ratio = Math.min(1.0, pct / 100.0);
            participationBarFill.prefWidthProperty().bind(
                participationBarPane.widthProperty().multiply(ratio));
            participationBarFill.maxWidthProperty().bind(
                participationBarPane.widthProperty().multiply(ratio));
        }

        if (participationSubLabel != null) {
            participationSubLabel.setText(
                dto.replyCount + " replies out of " + dto.postCount + " total posts");
        }

        // ── 3. Assessment history rows ───────────────────────────────
        renderAssessmentHistory(dto.assessmentHistory);

        // ── 4. Lecturer's remark ─────────────────────────────────────
        if (dto.latestRemark != null) {
            if (remarkLabel != null)
                remarkLabel.setText("\u201c" + dto.latestRemark.criteria + "\u201d");
            if (remarkMetaLabel != null)
                remarkMetaLabel.setText(
                    String.format("Score: %.1f  \u00B7  %s",
                        dto.latestRemark.score,
                        dto.latestRemark.createdAtHuman));
        }
    }

    private void renderActivityChart(List<StudentProgressDto.ActivityDay> days) {
        if (activityChartBox == null || activityLabelsBox == null) return;
        activityChartBox.getChildren().clear();
        activityLabelsBox.getChildren().clear();
        if (days == null || days.isEmpty()) return;

        int peak = days.stream().mapToInt(d -> d.count).max().orElse(1);
        for (StudentProgressDto.ActivityDay day : days) {
            double heightRatio = (double) day.count / Math.max(1, peak);
            double barH = Math.max(6, heightRatio * 120); // max 120px

            Region bar = new Region();
            boolean isToday = day.label != null && day.label.toLowerCase().contains("today");
            bar.setStyle("-fx-background-color: " + (day.count > 0 ? "#1e293b" : "#e2e8f0")
                + "; -fx-background-radius: 4 4 0 0;");
            bar.setPrefHeight(barH);
            bar.setMaxWidth(Double.MAX_VALUE);

            VBox col = new VBox(bar);
            col.setAlignment(Pos.BOTTOM_CENTER);
            col.setFillWidth(true);
            HBox.setHgrow(col, Priority.ALWAYS);
            activityChartBox.getChildren().add(col);

            Label lbl = new Label(day.label);
            lbl.setStyle("-fx-font-size: 11px; -fx-text-fill: #9ca3af;");
            lbl.setMaxWidth(Double.MAX_VALUE);
            lbl.setAlignment(javafx.geometry.Pos.CENTER);
            HBox.setHgrow(lbl, Priority.ALWAYS);
            activityLabelsBox.getChildren().add(lbl);
        }
    }

    private void renderAssessmentHistory(List<StudentProgressDto.AssessmentItem> items) {
        if (assessmentHistoryBox == null) return;
        assessmentHistoryBox.getChildren().clear();
        if (items == null || items.isEmpty()) {
            Label empty = new Label("No assessments completed yet.");
            empty.setStyle("-fx-text-fill: #9ca3af; -fx-font-size: 13px; -fx-padding: 8 0;");
            assessmentHistoryBox.getChildren().add(empty);
            return;
        }
        for (StudentProgressDto.AssessmentItem item : items) {
            HBox row = new HBox(10);
            row.setAlignment(Pos.CENTER_LEFT);
            row.setStyle("-fx-padding: 8 0;");

            VBox titleCol = new VBox(2);
            Label title = new Label(item.title);
            title.setStyle("-fx-font-weight: bold; -fx-text-fill: #1e293b;");
            Label sub = new Label("Completed " + (item.submittedAtHuman != null ? item.submittedAtHuman : ""));
            sub.setStyle("-fx-font-size: 11px; -fx-text-fill: #9ca3af;");
            titleCol.getChildren().addAll(title, sub);
            HBox.setHgrow(titleCol, Priority.ALWAYS);
            titleCol.setPrefWidth(300);

            Label score = new Label(String.format("%.1f%%", item.scorePct));
            score.setStyle("-fx-font-weight: bold; -fx-text-fill: #1e293b; -fx-alignment: center;");
            score.setPrefWidth(80);

            HBox peerBox = new HBox();
            peerBox.setAlignment(Pos.CENTER);
            peerBox.setPrefWidth(100);
            if (item.vsPeerPct != null) {
                boolean above = item.vsPeerPct >= 0;
                Label peer = new Label(String.format("%+.1f%%", item.vsPeerPct));
                peer.setStyle(above
                    ? "-fx-text-fill: #10b981; -fx-background-color: #d1fae5; -fx-padding: 2 6; -fx-background-radius: 10; -fx-font-size: 11px; -fx-font-weight: bold;"
                    : "-fx-text-fill: #ef4444; -fx-background-color: #fee2e2; -fx-padding: 2 6; -fx-background-radius: 10; -fx-font-size: 11px; -fx-font-weight: bold;");
                peerBox.getChildren().add(peer);
            }

            row.getChildren().addAll(titleCol, score, peerBox);
            assessmentHistoryBox.getChildren().add(row);

            // Divider between rows
            Region divider = new Region();
            divider.setStyle("-fx-background-color: #f3f4f6;");
            divider.setPrefHeight(1);
            assessmentHistoryBox.getChildren().add(divider);
        }
    }

    @FXML private void onDashboard() {
        User u = Session.currentUser();
        if (u != null) SceneManager.showHomeFor(u.getRole());
    }
    @FXML private void onGroups()    { SceneManager.goGroups(); }
    @FXML private void onNewTopic()  { SceneManager.goTopicCreation(); }
    @FXML private void onForum()     { SceneManager.goForumDashboard(); }
    @FXML private void onProfile()   { SceneManager.goProfile(); }
    @FXML private void onLogout()    {
        String token = Session.authToken();
        Session.end();
        new Thread(() -> new forum.services.AuthService().logout(token), "logout").start();
        SceneManager.show("Login", "ACES");
    }
}