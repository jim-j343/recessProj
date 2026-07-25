package forum.controllers;

import forum.api.ApiClient;
import forum.api.dto.StudentProgressDto;
import forum.app.Refreshable;
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
import javafx.scene.control.Separator;

import java.util.List;

public class StudentAssessmentController implements forum.app.Refreshable {

    @FXML private Label      avatarLabel;
    @FXML private Label      userNameLabel;
    @FXML private MenuButton notifButton;
    @FXML private Label      notifBadge;
    @FXML private Label      navNewTopic;

    // ── Sidebar ────────────────────────────────────────────────────────
    @FXML private Label sidebarUserName;
    @FXML private Label sidebarUserRole;


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

            if (sidebarUserName != null) {
                sidebarUserName.setText(u.displayName());
            }
            if (sidebarUserRole != null) {
                sidebarUserRole.setText(
                    switch (u.getRole()) {
                        case STUDENT -> "Student";
                        case LECTURER -> "Lecturer";
                        case SYSTEM_ADMIN -> "Administrator";
                    }
                );
            }
        }
        NavbarHelper.loadNotifications(api, notifButton, notifBadge);
        loadProgress();
    }

    @Override
    public void refresh() {
        forum.models.User user = forum.app.Session.currentUser();
        if (user != null) {
            userNameLabel.setText(user.displayName());
            avatarLabel.setText(String.valueOf(user.displayName().trim().charAt(0)).toUpperCase());
        }
        forum.util.NavbarHelper.loadNotifications(api, notifButton, notifBadge);
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
            lbl.setAlignment(Pos.CENTER);
            HBox.setHgrow(lbl, Priority.ALWAYS);
            activityLabelsBox.getChildren().add(lbl);
        }
    }

    private void renderAssessmentHistory(List<StudentProgressDto.AssessmentItem> items) {

    if (assessmentHistoryBox == null) return;

    assessmentHistoryBox.getChildren().clear();

    if (items == null || items.isEmpty()) {
        Label empty = new Label("No assessments completed yet.");
        empty.setStyle("-fx-text-fill:#9ca3af; -fx-font-size:13px;");
        assessmentHistoryBox.getChildren().add(empty);
        return;
    }

    for (StudentProgressDto.AssessmentItem item : items) {

        // ---------------- Quiz column ----------------

        Label title = new Label(item.title);
        title.setWrapText(true);
        title.setStyle(
            "-fx-font-size:14px;" +
            "-fx-font-weight:bold;" +
            "-fx-text-fill:#111827;"
        );

        Label date = new Label("Completed " + item.submittedAtHuman);
        date.setStyle(
            "-fx-font-size:12px;" +
            "-fx-text-fill:#94a3b8;"
        );

        VBox quizBox = new VBox(title, date);
        quizBox.setSpacing(2);

        HBox.setHgrow(quizBox, Priority.ALWAYS);
        quizBox.setMaxWidth(Double.MAX_VALUE);

        // ---------------- Score column ----------------

        Label score = new Label(String.format("%.1f%%", item.scorePct));
        score.setStyle(
            "-fx-font-size:14px;" +
            "-fx-font-weight:bold;" +
            "-fx-text-fill:#111827;"
        );

        StackPane scorePane = new StackPane(score);
        scorePane.setAlignment(Pos.CENTER);
        scorePane.setPrefWidth(90);
        scorePane.setMinWidth(90);
        scorePane.setMaxWidth(90);

        // ---------------- Peer column ----------------

        StackPane peerPane = new StackPane();
        peerPane.setAlignment(Pos.CENTER);
        peerPane.setPrefWidth(120);
        peerPane.setMinWidth(120);
        peerPane.setMaxWidth(120);

        if (item.vsPeerPct != null) {

            Label badge = new Label(String.format("%+.1f%%", item.vsPeerPct));

            if (item.vsPeerPct >= 0) {
                badge.setStyle(
                    "-fx-background-color:#DCFCE7;" +
                    "-fx-text-fill:#166534;" +
                    "-fx-background-radius:999;" +
                    "-fx-padding:4 10;" +
                    "-fx-font-size:11px;" +
                    "-fx-font-weight:bold;"
                );
            } else {
                badge.setStyle(
                    "-fx-background-color:#FEE2E2;" +
                    "-fx-text-fill:#991B1B;" +
                    "-fx-background-radius:999;" +
                    "-fx-padding:4 10;" +
                    "-fx-font-size:11px;" +
                    "-fx-font-weight:bold;"
                );
            }

            peerPane.getChildren().add(badge);
        }

        // ---------------- Row ----------------

        HBox row = new HBox(16);
        row.setAlignment(Pos.CENTER_LEFT);
        row.setFillHeight(true);

        row.getChildren().addAll(
            quizBox,
            scorePane,
            peerPane
        );

        assessmentHistoryBox.getChildren().add(row);

        Separator separator = new Separator();
        separator.setMaxWidth(Double.MAX_VALUE);

        assessmentHistoryBox.getChildren().add(separator);
    }
}

    // ── Navigation ───────────────────────────────────────────────────────
    @FXML private void onDashboard() {
        User u = Session.currentUser();
        if (u != null) SceneManager.showHomeFor(u.getRole());
    }
    @FXML private void onGroups()     { SceneManager.goGroups(); }
    @FXML private void onNewTopic()   { SceneManager.goTopicCreation(); }
    @FXML private void onForum()      { SceneManager.goForumDashboard(); }
    @FXML private void onProfile()    { SceneManager.goProfile(); }
    @FXML private void onQuizCenter() { SceneManager.goQuizManagement(); }
    @FXML private void onGrading()    { SceneManager.goParticipationGrading(); }
    @FXML private void onLogout() {
        String token = Session.authToken();
        Session.end();
        SceneManager.clearCache();
        new Thread(() -> new forum.services.AuthService().logout(token), "logout").start();
        SceneManager.show("Login", "ACES");
    }
}
