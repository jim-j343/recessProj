package forum.controllers;

import forum.api.ApiClient;
import forum.app.Session;
import forum.models.User;

import com.fasterxml.jackson.databind.ObjectMapper;
import javafx.application.Platform;
import javafx.fxml.FXML;
import javafx.scene.control.*;
import javafx.scene.layout.*;

import java.net.URI;
import java.net.http.HttpClient;
import java.net.http.HttpRequest;
import java.net.http.HttpResponse;
import java.time.Duration;
import java.util.*;

public class QuizManagementController implements forum.app.Refreshable {

    @FXML private Label    avatarLabel;
    @FXML private Label    userNameLabel;
    @FXML private Label    statusLabel;
    @FXML private Label    draftBanner;
    @FXML private TextField titleField;
    @FXML private TextField courseUnitField;
    @FXML private TextField startTimeField;
    @FXML private TextField durationField;
    @FXML private TextField categoryField;
    @FXML private VBox     questionsBox;

    private final ApiClient api = new ApiClient();
    private final List<QuestionEntry> questions = new ArrayList<>();
    private Long editingQuizId = null; // when non-null we're editing an existing draft

    @FXML
    private void initialize() {
        updateUserBadge();
        resetForm();
    }

    /** Populate the form for editing an existing quiz draft. */
    public void loadForEdit(forum.api.dto.QuizDto q) {
        if (q == null) return;
        this.editingQuizId = q.quizId;
        Platform.runLater(() -> {
            titleField.setText(q.title == null ? "" : q.title);
            courseUnitField.setText(q.courseName == null ? "" : q.courseName);
            durationField.setText(String.valueOf(q.durationMinutes));
            categoryField.setText(q.targetCategory == null ? "" : q.targetCategory);
            startTimeField.setText(q.startTime == null ? "" : q.startTime);
            if (draftBanner != null) {
                draftBanner.setManaged(true);
                draftBanner.setVisible(true);
            }
        });

        // Load full quiz questions via API and populate entries
        Thread t = new Thread(() -> {
            try {
                var detail = api.getQuiz(Session.authToken(), q.quizId);
                if (detail != null && detail.questions != null) {
                    Platform.runLater(() -> {
                        for (QuestionEntry e : new ArrayList<>(questions)) removeQuestion(e);
                        int idx = 1;
                        for (var qq : detail.questions) {
                            QuestionEntry entry = new QuestionEntry(idx++);
                            questions.add(entry);
                            questionsBox.getChildren().add(entry.buildCard(() -> removeQuestion(entry)));
                            entry.questionField.setText(qq.content);
                            List<String> ans = qq.answers.stream().map(a -> a.content).toList();
                            for (int i = 0; i < ans.size() && i < entry.answerFields.size(); i++) {
                                entry.answerFields.get(i).setText(ans.get(i));
                                try {
                                    var maybe = qq.answers.get(i);
                                    if (maybe != null && Boolean.TRUE.equals(maybe.isCorrect)) {
                                        entry.correctBtns.get(i).setSelected(true);
                                    }
                                } catch (Exception ignore) {}
                            }
                        }
                    });
                }
            } catch (Exception ex) {
                ex.printStackTrace();
            }
        }, "load-quiz-for-edit");
        t.setDaemon(true);
        t.start();
    }

    @Override
    public void refresh() {
        updateUserBadge();
        resetForm();
    }

    private void updateUserBadge() {
        User u = Session.currentUser();
        if (u != null) {
            userNameLabel.setText(u.displayName());
            if (avatarLabel != null) {
                String name = u.displayName();
                avatarLabel.setText(name == null || name.isBlank() ? "?" : String.valueOf(name.trim().charAt(0)).toUpperCase());
            }
        }
    }

    /**
     * Resets the form to a blank "create new quiz" state.
     *
     * This screen is cached (see SceneManager.UNCACHED_SCREENS), so without
     * this, navigating back to "Quiz Center" after previously editing a
     * draft would leave editingQuizId, the populated text fields, and the
     * loaded questions from that edit session still sitting in memory —
     * silently turning the next "create new quiz" submission into an
     * update of the OLD quiz instead. loadForEdit() always runs right
     * after this (see showAndGetController()'s refresh-then-populate
     * flow), so it's safe to unconditionally blank everything here first.
     */
    private void resetForm() {
        editingQuizId = null;
        titleField.clear();
        courseUnitField.clear();
        startTimeField.clear();
        durationField.clear();
        categoryField.clear();
        if (draftBanner != null) {
            draftBanner.setManaged(false);
            draftBanner.setVisible(false);
        }
        if (statusLabel != null) {
            statusLabel.setManaged(false);
            statusLabel.setVisible(false);
        }
        for (QuestionEntry e : new ArrayList<>(questions)) removeQuestion(e);
        onAddQuestion(); // start with one blank question
    }

    @FXML
    private void onAddQuestion() {
        int qNum = questions.size() + 1;
        QuestionEntry entry = new QuestionEntry(qNum);
        questions.add(entry);
        questionsBox.getChildren().add(entry.buildCard(() -> removeQuestion(entry)));
    }

    private void removeQuestion(QuestionEntry entry) {
        questions.remove(entry);
        questionsBox.getChildren().remove(entry.card);
    }

    @FXML private void onPublish()   { submit(true); }
    @FXML private void onSaveDraft() { submit(false); }

    private void submit(boolean publish) {
        String token = Session.authToken();
        if (token == null) { showStatus("Not authenticated."); return; }

        String title      = titleField.getText().trim();
        String courseUnit = courseUnitField.getText().trim();
        String start      = startTimeField.getText().trim();
        String duration   = durationField.getText().trim();

        if (title.isEmpty() || courseUnit.isEmpty() || start.isEmpty() || duration.isEmpty()) {
            showStatus("Please fill in all required fields.");
            return;
        }
        if (questions.isEmpty()) {
            showStatus("Add at least one question.");
            return;
        }
        for (QuestionEntry q : questions) {
            if (q.questionText().isBlank()) {
                showStatus("Question " + q.number + " has no text.");
                return;
            }
        }

        showStatus("Saving...");

        // Build JSON payload matching web quiz/store — course-based, not group-based
        Map<String, Object> body = new LinkedHashMap<>();
        body.put("title",       title);
        body.put("course_name", courseUnit);
        body.put("start_time",  start);
        body.put("duration",    Integer.parseInt(duration));
        body.put("target",      categoryField.getText().trim());
        if (publish) body.put("publish", true);

        List<Map<String, Object>> qList = new ArrayList<>();
        for (QuestionEntry q : questions) {
            Map<String, Object> qMap = new LinkedHashMap<>();
            qMap.put("text", q.questionText());
            qMap.put("answers", q.answerTexts());
            qMap.put("correct_answer", q.correctIndex());
            qList.add(qMap);
        }
        body.put("questions", qList);

        Thread worker = new Thread(() -> {
            try {
                ObjectMapper mapper = new ObjectMapper();
                String json = mapper.writeValueAsString(body);

                HttpClient http = HttpClient.newBuilder()
                        .connectTimeout(Duration.ofSeconds(10)).build();
                HttpRequest.Builder reqBuilder;
                String base = forum.config.DatabaseConfig.API_BASE_URL.replace("/api", "");
                if (editingQuizId != null) {
                    reqBuilder = HttpRequest.newBuilder(URI.create(base + "/quiz/" + editingQuizId))
                        .header("Authorization", "Bearer " + token)
                        .header("Accept", "application/json")
                        .header("Content-Type", "application/json")
                        .method("PUT", HttpRequest.BodyPublishers.ofString(json));
                } else {
                    reqBuilder = HttpRequest.newBuilder(URI.create(base + "/quiz/store"))
                        .header("Authorization", "Bearer " + token)
                        .header("Accept", "application/json")
                        .header("Content-Type", "application/json")
                        .POST(HttpRequest.BodyPublishers.ofString(json));
                }
                HttpRequest req = reqBuilder.build();
                HttpResponse<String> resp = http.send(req, HttpResponse.BodyHandlers.ofString());

                Platform.runLater(() -> {
                    if (resp.statusCode() == 200 || resp.statusCode() == 302) {
                        showStatus(publish ? "✓ Quiz published!" : "✓ Saved as draft.");
                        new Thread(() -> {
                            try { Thread.sleep(1200); } catch (InterruptedException ignored) {}
                            Platform.runLater(forum.app.SceneManager::goLecturerDashboard);
                        }).start();
                    } else {
                        showStatus("Failed (HTTP " + resp.statusCode() + "). Check your inputs.");
                    }
                });
            } catch (Exception e) {
                if (e instanceof InterruptedException) Thread.currentThread().interrupt();
                Platform.runLater(() -> showStatus("Error: " + e.getMessage()));
            }
        }, "create-quiz");
        worker.setDaemon(true);
        worker.start();
    }

    @FXML private void onDashboard() { forum.app.SceneManager.goLecturerDashboard(); }
    @FXML private void onGroups()    { forum.app.SceneManager.goGroups(); }
    @FXML private void onGrading()   { forum.app.SceneManager.goParticipationGrading(); }
    @FXML private void onNewTopic()  { forum.app.SceneManager.show("TopicCreation", "ACES — New Topic"); }
    @FXML private void onProfile()   { forum.app.SceneManager.goProfile(); }
    @FXML private void onLogout()    {
        String token = Session.authToken();
        Session.end();
        forum.app.SceneManager.clearCache();
        new Thread(() -> new forum.services.AuthService().logout(token), "logout").start();
        forum.app.SceneManager.show("Login", "ACES");
    }

    private void showStatus(String msg) {
        statusLabel.setText(msg);
        statusLabel.setManaged(true);
        statusLabel.setVisible(true);
    }

    static class QuestionEntry {
        final int number;
        VBox card;
        private TextField questionField;
        private final List<TextField>    answerFields  = new ArrayList<>();
        private final List<RadioButton>  correctBtns   = new ArrayList<>();
        private final ToggleGroup        toggle        = new ToggleGroup();

        QuestionEntry(int number) { this.number = number; }

        VBox buildCard(Runnable onRemove) {
            Label header = new Label("Question " + number);
            header.getStyleClass().add("label-strong");

            Button removeBtn = new Button("Remove");
            removeBtn.setStyle("-fx-text-fill:#ef4444; -fx-background-color:transparent;");
            removeBtn.setOnAction(e -> onRemove.run());

            Region spacer = new Region();
            HBox.setHgrow(spacer, Priority.ALWAYS);
            HBox topRow = new HBox(header, spacer, removeBtn);
            topRow.setAlignment(javafx.geometry.Pos.CENTER_LEFT);

            questionField = new TextField();
            questionField.setPromptText("Enter your question here...");

            Label hint = new Label("Select the radio button next to the correct answer.");
            hint.getStyleClass().add("subtle");

            VBox answersBox = new VBox(8);
            for (int i = 0; i < 4; i++) {
                TextField aField = new TextField();
                aField.setPromptText("Answer option " + (i + 1));
                HBox.setHgrow(aField, Priority.ALWAYS);

                RadioButton rb = new RadioButton();
                rb.setToggleGroup(toggle);
                if (i == 0) rb.setSelected(true);

                HBox aRow = new HBox(12, rb, aField);
                aRow.setAlignment(javafx.geometry.Pos.CENTER_LEFT);

                answerFields.add(aField);
                correctBtns.add(rb);
                answersBox.getChildren().add(aRow);
            }

            card = new VBox(12, topRow, questionField, answersBox, hint);
            card.setStyle("-fx-background-color:#f9fafb; -fx-border-color:#e5e7eb;"
                    + "-fx-border-width:1; -fx-background-radius:8;"
                    + "-fx-border-radius:8; -fx-padding:20;");
            return card;
        }

        String questionText() { return questionField == null ? "" : questionField.getText().trim(); }
        List<String> answerTexts() { return answerFields.stream().map(f -> f.getText().trim()).toList(); }
        int correctIndex() {
            for (int i = 0; i < correctBtns.size(); i++) if (correctBtns.get(i).isSelected()) return i;
            return 0;
        }
    }
}