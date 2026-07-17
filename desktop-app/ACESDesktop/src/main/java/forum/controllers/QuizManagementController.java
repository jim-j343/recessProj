package forum.controllers;

import forum.api.ApiClient;
import forum.api.ApiException;
import forum.api.dto.GroupDto;
import forum.app.SceneManager;
import forum.app.Session;
import forum.models.User;

import javafx.application.Platform;
import javafx.collections.FXCollections;
import javafx.fxml.FXML;
import javafx.scene.control.*;
import javafx.scene.layout.*;

import java.util.*;

public class QuizManagementController {

    @FXML private Label    avatarLabel;
    @FXML private Label    userNameLabel;
    @FXML private Label    statusLabel;
    @FXML private TextField titleField;
    @FXML private ComboBox<String> groupCombo; // course name, editable — mirrors web's <input list="course-options">
    @FXML private TextField startTimeField;
    @FXML private TextField durationField;
    @FXML private TextField categoryField;
    @FXML private VBox     questionsBox;

    private final ApiClient api = new ApiClient();
    private final List<QuestionEntry> questions = new ArrayList<>();

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
        if (groupCombo != null) groupCombo.setEditable(true);
        loadCourseNames();
        onAddQuestion(); // start with one question
    }

    /** Every distinct course unit in the system — mirrors QuizController::create() on
     *  web, which lets a lecturer target a course they don't personally belong to. */
    private void loadCourseNames() {
        String token = Session.authToken();
        if (token == null) return;
        Thread t = new Thread(() -> {
            try {
                List<GroupDto> groups = api.listGroups(token);
                List<String> courseNames = groups.stream()
                        .map(g -> g.courseName)
                        .filter(c -> c != null && !c.isBlank())
                        .distinct()
                        .sorted()
                        .toList();
                Platform.runLater(() -> groupCombo.setItems(FXCollections.observableArrayList(courseNames)));
            } catch (Exception e) {
                if (e instanceof InterruptedException) Thread.currentThread().interrupt();
            }
        }, "load-course-names");
        t.setDaemon(true);
        t.start();
    }

    @FXML
    private void onAddQuestion() {
        int qNum = questions.size() + 1;
        QuestionEntry entry = new QuestionEntry(qNum);
        questions.add(entry);
        questionsBox.getChildren().add(entry.buildCard(
                () -> removeQuestion(entry)));
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
        String start      = startTimeField.getText().trim();
        String duration   = durationField.getText().trim();
        String courseName = groupCombo.getEditor() != null
                ? groupCombo.getEditor().getText().trim()
                : (groupCombo.getValue() == null ? "" : groupCombo.getValue().trim());

        if (title.isEmpty() || start.isEmpty() || duration.isEmpty() || courseName.isEmpty()) {
            showStatus("Please fill in all required fields (title, course, start time, duration).");
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

        int durationMinutes;
        try {
            durationMinutes = Integer.parseInt(duration);
        } catch (NumberFormatException e) {
            showStatus("Duration must be a whole number of minutes.");
            return;
        }

        List<Map<String, Object>> qList = new ArrayList<>();
        for (QuestionEntry q : questions) {
            Map<String, Object> qMap = new LinkedHashMap<>();
            qMap.put("text", q.questionText());
            qMap.put("answers", q.answerTexts());
            qMap.put("correct_answer", q.correctIndex());
            qList.add(qMap);
        }

        Thread worker = new Thread(() -> {
            try {
                api.createQuiz(token, title, courseName, start, durationMinutes,
                        categoryField.getText().trim(), publish, qList);

                Platform.runLater(() -> {
                    showStatus(publish ? "✓ Quiz published!" : "✓ Saved as draft.");
                    new Thread(() -> {
                        try { Thread.sleep(1200); } catch (InterruptedException ignored) {}
                        Platform.runLater(SceneManager::goLecturerDashboard);
                    }).start();
                });
            } catch (ApiException e) {
                Platform.runLater(() -> showStatus("Failed: " + e.getMessage()));
            } catch (Exception e) {
                if (e instanceof InterruptedException) Thread.currentThread().interrupt();
                Platform.runLater(() -> showStatus("Error: " + e.getMessage()));
            }
        }, "create-quiz");
        worker.setDaemon(true);
        worker.start();
    }

    @FXML private void onDashboard() { SceneManager.goLecturerDashboard(); }
    @FXML private void onGroups()    { SceneManager.goGroups(); }
    @FXML private void onGrading()   { SceneManager.goParticipationGrading(); }
    @FXML private void onNewTopic()  { SceneManager.show("TopicCreation", "ACES — New Topic"); }

    @FXML private void onProfile()   { SceneManager.goProfile(); }
    @FXML private void onLogout()    {
        String token = Session.authToken();
        Session.end();
        new Thread(() -> new forum.services.AuthService().logout(token), "logout").start();
        SceneManager.show("Login", "ACES");
    }

    private void showStatus(String msg) {
        statusLabel.setText(msg);
        statusLabel.setManaged(true);
        statusLabel.setVisible(true);
    }

    // ── Inner helpers ──────────────────────────────────────────

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

        String questionText() {
            return questionField == null ? "" : questionField.getText().trim();
        }

        List<String> answerTexts() {
            return answerFields.stream().map(f -> f.getText().trim()).toList();
        }

        int correctIndex() {
            for (int i = 0; i < correctBtns.size(); i++) {
                if (correctBtns.get(i).isSelected()) return i;
            }
            return 0;
        }
    }
}