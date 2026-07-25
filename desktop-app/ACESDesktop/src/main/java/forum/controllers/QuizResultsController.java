package forum.controllers;

import forum.api.ApiClient;
import forum.api.dto.QuizResultDto;
import forum.app.SceneManager;
import forum.app.Session;
import forum.app.ViewState;
import forum.models.User;

import javafx.application.Platform;
import javafx.fxml.FXML;
import javafx.scene.control.Label;

import forum.app.Refreshable;

public class QuizResultsController implements Refreshable {

    @FXML private Label avatarLabel;
    @FXML private Label userNameLabel;
    @FXML private Label scoreLabel;
    @FXML private Label quizTitleLabel;
    @FXML private Label submittedLabel;
    @FXML private Label correctLabel;
    @FXML private Label incorrectLabel;
    @FXML private Label autoLabel;

    private final ApiClient api = new ApiClient();

    @FXML
    private void initialize() {
        User u = Session.currentUser();
        if (u != null) {
            userNameLabel.setText(u.displayName());
            if (avatarLabel != null) {
                String name = u.displayName();
                avatarLabel.setText(name == null || name.isBlank() ? "?" : String.valueOf(name.trim().charAt(0)).toUpperCase());
            }
        }

        loadResults();
    }

    @Override
    public void refresh() {
        User u = Session.currentUser();
        if (u != null) {
            userNameLabel.setText(u.displayName());
            if (avatarLabel != null) {
                String name = u.displayName();
                avatarLabel.setText(name == null || name.isBlank() ? "?" : String.valueOf(name.trim().charAt(0)).toUpperCase());
            }
        }
        loadResults();
    }

    private void loadResults() {
        var quiz = ViewState.getSelectedQuiz();
        if (quiz == null) { SceneManager.goStudentDashboard(); return; }

        quizTitleLabel.setText(quiz.title);
        scoreLabel.setText("Loading...");

        String token = Session.authToken();
        if (token == null) { SceneManager.goStudentDashboard(); return; }

        Thread t = new Thread(() -> {
            try {
                QuizResultDto result = api.myQuizResult(token, quiz.quizId);
                Platform.runLater(() -> render(result));
            } catch (Exception e) {
                if (e instanceof InterruptedException) Thread.currentThread().interrupt();
                Platform.runLater(SceneManager::goStudentDashboard);
            }
        }, "load-results");
        t.setDaemon(true);
        t.start();
    }

    private void render(QuizResultDto result) {
        int score   = (int) result.score;
        int total   = result.total;
        int pct     = total > 0 ? Math.round((float) score / total * 100) : 0;
        int correct = score;
        int wrong   = total - correct;

        scoreLabel.setText(score + " / " + total + " (" + pct + "%)");
        correctLabel.setText(String.valueOf(correct));
        incorrectLabel.setText(String.valueOf(wrong));
        autoLabel.setText(result.autoSubmitted ? "Auto" : "Manual");

        if (result.submittedAt != null) {
            submittedLabel.setText("Submitted: " +
                    result.submittedAt.replace("T", " ").substring(0, 16));
        }
    }

    @FXML
    private void onDashboard() { SceneManager.goStudentDashboard(); }
    @FXML private void onProfile()   { SceneManager.goProfile(); }
    @FXML private void onLogout()    {
        String token = Session.authToken();
        Session.end();
        SceneManager.clearCache();
        new Thread(() -> new forum.services.AuthService().logout(token), "logout").start();
        SceneManager.show("Login", "ACES");
    }
}
