package forum.controllers;

import forum.api.ApiClient;
import forum.api.dto.QuizDetailResponse;
import forum.app.SceneManager;
import forum.app.Session;
import forum.app.ViewState;

import javafx.animation.Animation;
import javafx.animation.KeyFrame;
import javafx.animation.Timeline;
import javafx.application.Platform;
import javafx.fxml.FXML;
import javafx.scene.control.*;
import javafx.scene.layout.Region;
import javafx.scene.layout.StackPane;
import javafx.scene.layout.VBox;
import javafx.util.Duration;

import java.util.HashMap;
import java.util.List;
import java.util.Map;

public class QuizFocusModeController {

    @FXML private Label     timerLabel;
    @FXML private StackPane progressBarPane;  // outer container (gray track)
    @FXML private Region    progressFill;     // indigo fill
    @FXML private Label       quizTitleLabel;
    @FXML private Label       questionIndexLabel;
    @FXML private Label       questionTextLabel;
    @FXML private VBox        answersBox;
    @FXML private Button      prevBtn;
    @FXML private Button      nextBtn;
    @FXML private Button      submitBtn;

    private QuizDetailResponse detail;
    private List<QuizDetailResponse.Question> questions;
    private int currentIndex = 0;
    private final Map<Long, Long> selectedAnswers = new HashMap<>();
    private Timeline timer;
    private long secondsLeft;
    private final ApiClient api = new ApiClient();

    @FXML
    private void initialize() {
        detail = ViewState.getSelectedQuizDetail();
        if (detail == null || detail.questions == null || detail.questions.isEmpty()) {
            quizTitleLabel.setText("No quiz loaded.");
            return;
        }

        questions = detail.questions;
        quizTitleLabel.setText(detail.quiz.title);

        // Calculate time left
        secondsLeft = (long) detail.quiz.durationMinutes * 60;
        try {
            java.time.OffsetDateTime start = java.time.OffsetDateTime.parse(detail.quiz.startTime);
            long elapsed = java.time.Duration.between(start,
                    java.time.OffsetDateTime.now()).getSeconds();
            secondsLeft = Math.max(0, secondsLeft - elapsed);
        } catch (Exception ignored) {}

        startTimer();
        showQuestion(0);
    }

    private void startTimer() {
        timer = new Timeline(new KeyFrame(Duration.seconds(1), e -> {
            secondsLeft--;
            long m = secondsLeft / 60;
            long s = secondsLeft % 60;
            timerLabel.setText(String.format("%02d:%02d", m, s));
            if (secondsLeft <= 0) {
                timer.stop();
                autoSubmit();
            }
        }));
        timer.setCycleCount(Animation.INDEFINITE);
        timer.play();
    }

    private void showQuestion(int index) {
        if (questions == null || questions.isEmpty()) return;
        currentIndex = index;
        QuizDetailResponse.Question q = questions.get(index);

        questionIndexLabel.setText("Question " + (index + 1) + " of " + questions.size()
                + " · Multiple Choice");
        questionTextLabel.setText(q.content);

        // Update indigo progress fill — bind width to ratio of questions answered
        if (progressFill != null && progressBarPane != null) {
            double ratio = (double)(index + 1) / questions.size();
            progressFill.prefWidthProperty().unbind();
            progressFill.maxWidthProperty().unbind();
            progressFill.prefWidthProperty().bind(
                progressBarPane.widthProperty().multiply(ratio));
            progressFill.maxWidthProperty().bind(
                progressBarPane.widthProperty().multiply(ratio));
        }

        answersBox.getChildren().clear();
        ToggleGroup group = new ToggleGroup();

        for (QuizDetailResponse.Answer a : q.answers) {
            RadioButton rb = new RadioButton(a.content);
            rb.setToggleGroup(group);
            rb.setWrapText(true);
            rb.getStyleClass().add("answer-option");
            rb.setMaxWidth(Double.MAX_VALUE);

            if (selectedAnswers.containsKey(q.questionId)
                    && selectedAnswers.get(q.questionId) == a.answerId) {
                rb.setSelected(true);
            }

            final long answerId = a.answerId;
            rb.setOnAction(e -> selectedAnswers.put(q.questionId, answerId));
            answersBox.getChildren().add(rb);
        }

        prevBtn.setDisable(index == 0);
        boolean isLast = index == questions.size() - 1;
        nextBtn.setManaged(!isLast);
        nextBtn.setVisible(!isLast);
        submitBtn.setManaged(isLast);
        submitBtn.setVisible(isLast);
    }

    @FXML private void onPrev() { if (currentIndex > 0) showQuestion(currentIndex - 1); }
    @FXML private void onNext() { if (questions != null && currentIndex < questions.size() - 1) showQuestion(currentIndex + 1); }

    @FXML
    private void onSubmit() {
        if (timer != null) timer.stop();
        doSubmit(false);
    }

    private void autoSubmit() {
        Platform.runLater(() -> doSubmit(true));
    }

    private void doSubmit(boolean auto) {
        String token = Session.authToken();
        if (token == null) { SceneManager.goStudentDashboard(); return; }

        long quizId = detail.quiz.quizId;
        Thread t = new Thread(() -> {
            try {
                api.submitQuiz(token, quizId, selectedAnswers, auto);
                Platform.runLater(SceneManager::goQuizResults);
            } catch (Exception e) {
                if (e instanceof InterruptedException) Thread.currentThread().interrupt();
                Platform.runLater(SceneManager::goStudentDashboard);
            }
        }, "submit-quiz");
        t.setDaemon(true);
        t.start();
    }
}
