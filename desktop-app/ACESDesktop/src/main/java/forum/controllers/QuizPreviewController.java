package forum.controllers;

import forum.api.ApiClient;
import forum.api.dto.QuizDetailResponse;
import forum.api.dto.QuizResultDto;
import forum.app.Refreshable;
import forum.app.SceneManager;
import forum.app.Session;
import forum.app.ViewState;
import forum.models.User;

import javafx.application.Platform;
import javafx.fxml.FXML;
import javafx.scene.control.Label;
import javafx.scene.control.ListView;
import javafx.scene.control.Button;
import javafx.scene.layout.VBox;

import java.util.List;

public class QuizPreviewController implements Refreshable {

    @FXML private Label avatarLabel;
    @FXML private Label userNameLabel;
    @FXML private Label quizTitleLabel;
    @FXML private Label statsLabel;
    @FXML private ListView<String> submissionsList;
    @FXML private VBox questionsBox;
    @FXML private Button editBtn;
    @FXML private Button publishBtn;
    @FXML private Button backBtn;

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
        loadPreview();
    }

    @Override
    public void refresh() { loadPreview(); }

    private void loadPreview() {
        var q = ViewState.getSelectedQuiz();
        if (q == null) { SceneManager.goLecturerDashboard(); return; }
        quizTitleLabel.setText(q.title == null ? "" : q.title);
        submissionsList.getItems().clear();
        questionsBox.getChildren().clear();
        // Toggle edit/publish buttons for drafts
        boolean isDraft = !q.isPublished;
        editBtn.setVisible(isDraft);
        editBtn.setManaged(isDraft);
        publishBtn.setVisible(isDraft);
        publishBtn.setManaged(isDraft);

        String token = Session.authToken();
        if (token == null) return;

        new Thread(() -> {
            try {
                var detail = ViewState.getSelectedQuizDetail();
                if (detail == null) detail = api.getQuiz(token, q.quizId);
                List<QuizResultDto> results = api.allQuizResults(token, q.quizId);
                int total = detail == null || detail.questions == null ? 0 : detail.questions.stream().mapToInt(x -> x.marks).sum();
                double avg = 0;
                if (results != null && !results.isEmpty() && total > 0) {
                    avg = results.stream().mapToDouble(r -> (double) r.score / total * 100).average().orElse(0);
                }
                double finalAvg = avg;
                List<QuizResultDto> finalResults = results == null ? List.of() : results;
                Platform.runLater(() -> {
                    statsLabel.setText("Completed: " + finalResults.size() + " · Avg: " + (finalAvg > 0 ? String.format("%.1f%%", finalAvg) : "—"));
                    // Render question list (no correct answer markings available via API)
                    if (detail != null && detail.questions != null) {
                        for (var qq : detail.questions) {
                            String qtxt = qq.content + " (" + qq.marks + " mark)";
                            javafx.scene.control.Label qLabel = new javafx.scene.control.Label(qtxt);
                            qLabel.getStyleClass().add("label-strong");
                            VBox answers = new VBox(4);
                            for (var a : qq.answers) {
                                javafx.scene.control.Label aLabel = new javafx.scene.control.Label("• " + a.content);
                                aLabel.getStyleClass().add("muted");
                                answers.getChildren().add(aLabel);
                            }
                            VBox card = new VBox(6, qLabel, answers);
                            card.setStyle("-fx-padding:10; -fx-border-color:#e5e7eb; -fx-background-color:#fff; -fx-border-radius:6; -fx-background-radius:6;");
                            questionsBox.getChildren().add(card);
                        }
                    }

                    for (QuizResultDto r : finalResults) {
                        submissionsList.getItems().add(r.username + " — " + r.score + "/" + r.total + (r.autoSubmitted ? " (Auto)" : "") + (r.submittedAt != null ? " — " + r.submittedAt.replace('T',' ').substring(0,16) : ""));
                    }
                });
            } catch (Exception e) {
                e.printStackTrace();
            }
        }, "load-quiz-preview").start();
    }

    @FXML
    private void onEdit() {
        var q = ViewState.getSelectedQuiz();
        if (q == null) return;
        ViewState.setSelectedQuiz(q);
        var ctl = SceneManager.showAndGetController("QuizManagement", "ACES — Edit Quiz");
        if (ctl != null) ctl.loadForEdit(q);
    }

    @FXML
    private void onPublish() {
        var q = ViewState.getSelectedQuiz();
        if (q == null) return;
        String token = Session.authToken();
        if (token == null) return;
        publishBtn.setDisable(true);
        new Thread(() -> {
            try {
                api.publishQuiz(token, q.quizId);
                Platform.runLater(() -> {
                    publishBtn.setDisable(false);
                    // Refresh preview to show published state
                    refresh();
                });
            } catch (Exception e) {
                e.printStackTrace();
                Platform.runLater(() -> publishBtn.setDisable(false));
            }
        }, "publish-quiz").start();
    }

    @FXML private void onBack() { SceneManager.goLecturerDashboard(); }
    @FXML private void onProfile() { SceneManager.goProfile(); }
    @FXML private void onLogout() {
        String token = Session.authToken();
        Session.end();
        SceneManager.clearCache();
        new Thread(() -> new forum.services.AuthService().logout(token), "logout").start();
        SceneManager.show("Login", "ACES");
    }
}
