package forum.controllers;

import forum.api.ApiClient;
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
import javafx.scene.layout.HBox;

import java.time.ZonedDateTime;
import java.time.LocalDateTime;
import java.time.format.DateTimeFormatter;
import java.time.format.DateTimeParseException;
import java.util.List;

public class QuizPreviewController implements Refreshable {

    @FXML private Label avatarLabel;
    @FXML private Label userNameLabel;
    @FXML private Label quizTitleLabel;
    @FXML private Label quizMetaLabel;
    @FXML private Label statusBadge;
    @FXML private Label opensLabel;
    @FXML private Label durationLabel;
    @FXML private Label totalMarksLabel;
    @FXML private Label statsLabel;
    @FXML private Label questionsHeaderLabel;
    @FXML private ListView<String> submissionsList;
    @FXML private VBox questionsBox;
    @FXML private Button editBtn;
    @FXML private Button publishBtn;

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
        String meta = (q.courseName == null || q.courseName.isBlank() ? "" : q.courseName + " · ")
                + "applies to " + q.eligibleGroupCount + " group" + (q.eligibleGroupCount == 1 ? "" : "s");
        quizMetaLabel.setText(meta);

        statusBadge.setText(q.isPublished ? "Published" : "Draft");
        statusBadge.getStyleClass().removeAll("badge-success", "badge-neutral");
        statusBadge.getStyleClass().add(q.isPublished ? "badge-success" : "badge-neutral");

        opensLabel.setText(formatDateTime(q.startTime));
        durationLabel.setText(q.durationMinutes + " mins");
        totalMarksLabel.setText(String.valueOf(q.totalMarks));

        submissionsList.getItems().clear();
        questionsBox.getChildren().clear();

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
                int total = detail == null || detail.questions == null ? 0
                        : detail.questions.stream().mapToInt(x -> x.marks).sum();
                double avg = 0;
                if (results != null && !results.isEmpty() && total > 0) {
                    avg = results.stream().mapToDouble(r -> (double) r.score / total * 100).average().orElse(0);
                }
                double finalAvg = avg;
                List<QuizResultDto> finalResults = results == null ? List.of() : results;
                var finalDetail = detail;
                Platform.runLater(() -> {
                    statsLabel.setText(finalResults.isEmpty() ? "No completed submissions yet."
                            : String.format("%.1f%%", finalAvg));

                    if (finalDetail != null && finalDetail.questions != null) {
                        questionsHeaderLabel.setText("Questions (" + finalDetail.questions.size() + ")");
                        for (var qq : finalDetail.questions) {
                            Label qLabel = new Label(qq.content + " (" + qq.marks + " mark)");
                            qLabel.getStyleClass().add("label-strong");
                            VBox answers = new VBox(4);
                            for (var a : qq.answers) {
                                boolean correct = Boolean.TRUE.equals(a.isCorrect);
                                Label aLabel = new Label((correct ? "✓ " : "○ ") + a.content);
                                aLabel.getStyleClass().add(correct ? "answer-correct" : "muted");
                                if (correct) aLabel.setStyle("-fx-text-fill:#16a34a; -fx-font-weight:bold;");
                                answers.getChildren().add(aLabel);
                            }
                            VBox card = new VBox(6, qLabel, answers);
                            card.setStyle("-fx-padding:10; -fx-border-color:#e5e7eb; -fx-background-color:#fff; -fx-border-radius:6; -fx-background-radius:6;");
                            questionsBox.getChildren().add(card);
                        }
                    } else {
                        questionsHeaderLabel.setText("Questions (0)");
                    }

                    for (QuizResultDto r : finalResults) {
                        submissionsList.getItems().add(r.username + " — " + r.score + "/" + r.total
                                + (r.autoSubmitted ? " (Auto)" : "")
                                + (r.submittedAt != null ? " — " + r.submittedAt.replace('T', ' ').substring(0, 16) : ""));
                    }
                });
            } catch (Exception e) {
                e.printStackTrace();
                Platform.runLater(() -> statsLabel.setText("Couldn't load this quiz: " + e.getMessage()));
            }
        }, "load-quiz-preview").start();
    }

    private String formatDateTime(String raw) {
        if (raw == null || raw.isBlank()) return "—";
        try {
            return ZonedDateTime.parse(raw).format(DateTimeFormatter.ofPattern("d MMM yyyy, HH:mm"));
        } catch (DateTimeParseException e) {
            try {
                return LocalDateTime.parse(raw).format(DateTimeFormatter.ofPattern("d MMM yyyy, HH:mm"));
            } catch (DateTimeParseException ex) {
                return raw;
            }
        }
    }

    @FXML
    private void onEdit() {
        var q = ViewState.getSelectedQuiz();
        if (q == null) return;
        QuizManagementController ctl = SceneManager.showAndGetController("QuizManagement", "ACES — Edit Quiz");
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
                Platform.runLater(() -> { publishBtn.setDisable(false); refresh(); });
            } catch (Exception e) {
                e.printStackTrace();
                Platform.runLater(() -> {
                    publishBtn.setDisable(false);
                    new javafx.scene.control.Alert(javafx.scene.control.Alert.AlertType.ERROR,
                            "Couldn't publish this quiz.\n\n" + e.getMessage())
                            .showAndWait();
                });
            }
        }, "publish-quiz").start();
    }

    @FXML private void onBack()       { SceneManager.goLecturerDashboard(); }
    @FXML private void onDashboard()  { SceneManager.goLecturerDashboard(); }
    @FXML private void onGroups()     { SceneManager.goGroups(); }
    @FXML private void onNewTopic()   { SceneManager.show("TopicCreation", "ACES — New Topic"); }
    @FXML private void onQuizCenter() { SceneManager.goQuizManagement(); }
    @FXML private void onGrading()    { SceneManager.goParticipationGrading(); }
    @FXML private void onProfile()    { SceneManager.goProfile(); }
    @FXML private void onLogout() {
        String token = Session.authToken();
        Session.end();
        SceneManager.clearCache();
        new Thread(() -> new forum.services.AuthService().logout(token), "logout").start();
        SceneManager.show("Login", "ACES");
    }
}