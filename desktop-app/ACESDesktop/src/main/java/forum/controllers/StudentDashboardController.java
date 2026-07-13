package forum.controllers;

import forum.api.ApiClient;
import forum.api.ApiException;
import forum.api.dto.QuizDto;
import forum.api.dto.QuizResultDto;
import forum.app.SceneManager;
import forum.app.Session;
import forum.app.ViewState;
import forum.models.User;
import forum.services.AuthService;

import javafx.application.Platform;
import javafx.fxml.FXML;
import javafx.geometry.Insets;
import javafx.scene.control.Button;
import javafx.scene.control.Label;
import javafx.scene.control.ProgressBar;
import javafx.scene.layout.HBox;
import javafx.scene.layout.Region;
import javafx.scene.layout.VBox;

import java.util.List;

public class StudentDashboardController {

    @FXML private Label       avatarLabel;
    @FXML private Label       userNameLabel;
    @FXML private Label       welcomeLabel;
    @FXML private HBox        quizAlertBox;
    @FXML private Label       quizAlertIcon;
    @FXML private Label       quizAlertTitle;
    @FXML private Label       quizAlertSub;
    @FXML private Button      takeQuizBtn;
    @FXML private Label       quizProgressLabel;
    @FXML private ProgressBar quizProgressBar;
    @FXML private Label       quizProgressSub;
    @FXML private Label       participationLabel;
    @FXML private Label       standingLabel;
    @FXML private Label       standingSub;
    @FXML private VBox        resultsBox;
    @FXML private Label       noResultsLabel;

    private final ApiClient api = new ApiClient();

    @FXML
    private void initialize() {
        User u = Session.currentUser();
        if (u != null) {
            String initials = u.displayName().length() >= 2
                    ? u.displayName().substring(0, 2).toUpperCase()
                    : u.displayName().toUpperCase();
            avatarLabel.setText(initials);
            userNameLabel.setText(u.displayName());
            welcomeLabel.setText("Welcome back, " + u.displayName() + " 👋");
        }
        loadInBackground();
    }

    private void loadInBackground() {
        String token = Session.authToken();
        if (token == null) return;

        Thread worker = new Thread(() -> {
            try {
                List<QuizDto> quizzes = api.listQuizzes(token);
                Platform.runLater(() -> renderQuizAlert(quizzes));

                // stats
                int total     = quizzes.size();
                int completed = 0;
                Platform.runLater(() -> resultsBox.getChildren().clear());

                for (QuizDto q : quizzes) {
                    try {
                        QuizResultDto result = api.myQuizResult(token, q.quizId);
                        completed++;
                        Platform.runLater(() -> addResultRow(q.title, result));
                    } catch (Exception ignored) {
                        // not yet submitted
                    }
                }

                int finalCompleted = completed;
                int finalTotal     = total;
                Platform.runLater(() -> {
                    quizProgressLabel.setText(finalCompleted + "/" + finalTotal);
                    quizProgressBar.setProgress(finalTotal > 0 ? (double) finalCompleted / finalTotal : 0);
                    quizProgressSub.setText(finalTotal > 0
                            ? Math.round((double) finalCompleted / finalTotal * 100) + "% of published quizzes completed"
                            : "No quizzes published yet.");
                    if (finalCompleted == 0 && finalTotal == 0) {
                        noResultsLabel.setManaged(true);
                        noResultsLabel.setVisible(true);
                    }
                });

            } catch (ApiException | java.io.IOException | InterruptedException e) {
                if (e instanceof InterruptedException) Thread.currentThread().interrupt();
            }
        }, "student-dashboard-load");
        worker.setDaemon(true);
        worker.start();
    }

    private void renderQuizAlert(List<QuizDto> quizzes) {
        QuizDto active = quizzes.stream()
                .filter(q -> q.isPublished && isNowActive(q))
                .findFirst().orElse(null);

        if (active != null) {
            quizAlertBox.getStyleClass().removeAll("alert-gray");
            quizAlertBox.getStyleClass().add("alert-warning");
            quizAlertIcon.setText("⚠️");
            quizAlertTitle.setText("Live Quiz: " + active.title);
            quizAlertSub.setText("This quiz is active now. It will auto-submit when time expires.");
            takeQuizBtn.setManaged(true);
            takeQuizBtn.setVisible(true);
            ViewState.setSelectedQuiz(active);
        } else {
            quizAlertIcon.setText("✓");
            quizAlertTitle.setText("No Quizzes Pending");
            quizAlertSub.setText("You're all caught up — nothing active right now.");
        }
    }

    private boolean isNowActive(QuizDto q) {
        if (q.startTime == null) return false;
        try {
            java.time.OffsetDateTime start = java.time.OffsetDateTime.parse(q.startTime);
            java.time.OffsetDateTime end   = start.plusMinutes(q.durationMinutes);
            java.time.OffsetDateTime now   = java.time.OffsetDateTime.now();
            return now.isAfter(start) && now.isBefore(end);
        } catch (Exception e) { return false; }
    }

    private void addResultRow(String quizTitle, QuizResultDto result) {
        int pct = result.total > 0 ? (int) Math.round((result.score / result.total) * 100) : 0;

        Label title  = new Label(quizTitle);
        title.getStyleClass().add("label-strong");

        Label score  = new Label(pct + "%");
        score.getStyleClass().add("label-strong");

        Region spacer = new Region();
        HBox.setHgrow(spacer, javafx.scene.layout.Priority.ALWAYS);

        HBox row = new HBox(title, spacer, score);
        row.setPadding(new Insets(10, 0, 10, 0));
        row.setAlignment(javafx.geometry.Pos.CENTER_LEFT);

        if (!resultsBox.getChildren().isEmpty()) {
            Region div = new Region();
            div.getStyleClass().add("divider");
            div.setPrefHeight(1);
            resultsBox.getChildren().add(div);
        }
        resultsBox.getChildren().add(row);
    }

    @FXML private void onDashboard() { SceneManager.goStudentDashboard(); }
    @FXML private void onGroups()    { SceneManager.goGroups(); }
    @FXML private void onForum()     { SceneManager.goForumDashboard(); }

    @FXML
    private void onTakeQuiz() {
        QuizDto q = ViewState.getSelectedQuiz();
        if (q == null) return;
        String token = Session.authToken();
        if (token == null) return;
        Thread t = new Thread(() -> {
            try {
                var detail = api.getQuiz(token, q.quizId);
                ViewState.setSelectedQuizDetail(detail);
                Platform.runLater(SceneManager::goQuizFocusMode);
            } catch (Exception e) {
                e.printStackTrace();
            }
        }, "load-quiz");
        t.setDaemon(true);
        t.start();
    }

    @FXML private void onProfile() { forum.app.SceneManager.goProfile(); }

    @FXML
    private void onLogout() {
        String token = Session.authToken();
        Session.end();
        new Thread(() -> new AuthService().logout(token), "logout").start();
        SceneManager.show("Login", "Smart Discussion Forum");
    }
}