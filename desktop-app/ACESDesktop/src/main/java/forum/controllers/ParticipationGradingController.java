package forum.controllers;

import forum.api.ApiClient;
import forum.api.ApiException;
import forum.app.SceneManager;
import forum.app.Session;
import forum.models.User;

import com.fasterxml.jackson.databind.JsonNode;
import com.fasterxml.jackson.databind.ObjectMapper;
import javafx.application.Platform;
import javafx.beans.property.SimpleStringProperty;
import javafx.collections.FXCollections;
import javafx.collections.ObservableList;
import javafx.fxml.FXML;
import javafx.scene.control.*;
import javafx.scene.layout.HBox;

import java.net.URI;
import java.net.http.HttpClient;
import java.net.http.HttpRequest;
import java.net.http.HttpResponse;
import java.time.Duration;
import java.util.ArrayList;
import java.util.List;

public class ParticipationGradingController {

    @FXML private Label     avatarLabel;
    @FXML private Label     userNameLabel;
    @FXML private Label     statusLabel;
    @FXML private TableView<GradeRow> gradesTable;
    @FXML private TableColumn<GradeRow, String> colStudent;
    @FXML private TableColumn<GradeRow, String> colReplies;
    @FXML private TableColumn<GradeRow, String> colParticipation;
    @FXML private TableColumn<GradeRow, String> colTestAvg;
    @FXML private TableColumn<GradeRow, String> colScore;
    @FXML private TableColumn<GradeRow, String> colRemarks;

    private final ObservableList<GradeRow> rows = FXCollections.observableArrayList();
    private final ApiClient api = new ApiClient();

    @FXML
    private void initialize() {
        User u = Session.currentUser();
        if (u != null) {
            userNameLabel.setText(u.displayName());
            if (avatarLabel != null) {
                String name = u.displayName().trim();
                avatarLabel.setText(name.length() >= 2 ? name.substring(0, 2).toUpperCase() : name.toUpperCase());
            }
        }

        colStudent.setCellValueFactory(c -> new SimpleStringProperty(c.getValue().username));
        colReplies.setCellValueFactory(c -> new SimpleStringProperty(String.valueOf(c.getValue().replyCount)));
        colParticipation.setCellValueFactory(c -> new SimpleStringProperty(c.getValue().participationPct + "%"));
        colTestAvg.setCellValueFactory(c -> {
            Double avg = c.getValue().quizAvgPct;
            return new SimpleStringProperty(avg != null ? avg + "% (" + c.getValue().quizCount + " quizzes)" : "No quizzes");
        });

        // Editable score field
        colScore.setCellFactory(col -> new TableCell<>() {
            private final TextField field = new TextField();
            {
                field.setOnKeyReleased(e -> {
                    GradeRow row = getTableView().getItems().get(getIndex());
                    row.score = field.getText();
                });
            }
            @Override protected void updateItem(String item, boolean empty) {
                super.updateItem(item, empty);
                if (empty) { setGraphic(null); return; }
                GradeRow row = getTableView().getItems().get(getIndex());
                field.setText(row.score);
                setGraphic(field);
            }
        });

        // Editable remarks field
        colRemarks.setCellFactory(col -> new TableCell<>() {
            private final TextField field = new TextField();
            {
                field.setOnKeyReleased(e -> {
                    GradeRow row = getTableView().getItems().get(getIndex());
                    row.remarks = field.getText();
                });
            }
            @Override protected void updateItem(String item, boolean empty) {
                super.updateItem(item, empty);
                if (empty) { setGraphic(null); return; }
                field.setText(getTableView().getItems().get(getIndex()).remarks);
                setGraphic(field);
            }
        });

        gradesTable.setItems(rows);
        loadStudents();
    }

    private void loadStudents() {
        String token = Session.authToken();
        if (token == null) { showStatus("Offline — cannot load students."); return; }

        Thread worker = new Thread(() -> {
            try {
                // Call the web participation grade endpoint via API
                HttpClient http = HttpClient.newBuilder()
                        .connectTimeout(Duration.ofSeconds(8)).build();
                HttpRequest req = HttpRequest.newBuilder(
                        URI.create(forum.config.DatabaseConfig.API_BASE_URL + "/participation/grade-json"))
                        .header("Authorization", "Bearer " + token)
                        .header("Accept", "application/json")
                        .GET().build();

                HttpResponse<String> resp = http.send(req, HttpResponse.BodyHandlers.ofString());

                if (resp.statusCode() == 200) {
                    ObjectMapper mapper = new ObjectMapper();
                    JsonNode root = mapper.readTree(resp.body());
                    List<GradeRow> result = new ArrayList<>();
                    for (JsonNode node : root) {
                        GradeRow row = new GradeRow();
                        row.userId           = node.get("user_id").asLong();
                        row.username         = node.get("username").asText();
                        row.replyCount       = node.get("reply_count").asInt();
                        row.participationPct = node.get("participation_pct").asDouble();
                        row.quizAvgPct       = node.hasNonNull("quiz_avg_pct") ? node.get("quiz_avg_pct").asDouble() : null;
                        row.quizCount        = node.get("quiz_count").asInt();
                        row.suggestedScore   = node.get("suggested_score").asDouble();
                        row.score            = node.hasNonNull("existing_score") ? node.get("existing_score").asText() : String.valueOf(row.suggestedScore);
                        row.remarks          = "";
                        result.add(row);
                    }
                    Platform.runLater(() -> rows.setAll(result));
                } else {
                    Platform.runLater(() -> showStatus("Could not load students (HTTP " + resp.statusCode() + ")."));
                }
            } catch (Exception e) {
                if (e instanceof InterruptedException) Thread.currentThread().interrupt();
                Platform.runLater(() -> showStatus("Could not load students: " + e.getMessage()));
            }
        }, "load-grading");
        worker.setDaemon(true);
        worker.start();
    }

    @FXML
    private void onSaveGrades() {
        String token = Session.authToken();
        if (token == null) return;

        Thread worker = new Thread(() -> {
            try {
                StringBuilder json = new StringBuilder("{\"grades\":{");
                boolean first = true;
                for (GradeRow row : rows) {
                    if (row.score == null || row.score.isBlank()) continue;
                    if (!first) json.append(",");
                    json.append("\"").append(row.userId).append("\":")
                        .append("{\"score\":\"").append(row.score).append("\",")
                        .append("\"remark\":\"").append(
                                row.remarks != null ? row.remarks.replace("\"", "'") : "")
                        .append("\"}");
                    first = false;
                }
                json.append("}}");

                HttpClient http = HttpClient.newBuilder()
                        .connectTimeout(Duration.ofSeconds(8)).build();
                HttpRequest req = HttpRequest.newBuilder(
                        URI.create(forum.config.DatabaseConfig.API_BASE_URL + "/participation/grade-json"))
                        .header("Authorization", "Bearer " + token)
                        .header("Accept", "application/json")
                        .header("Content-Type", "application/json")
                        .POST(HttpRequest.BodyPublishers.ofString(json.toString()))
                        .build();

                HttpResponse<String> resp = http.send(req, HttpResponse.BodyHandlers.ofString());
                Platform.runLater(() -> showStatus(
                        resp.statusCode() == 200 ? "✓ Grades saved." : "Save failed — try again."));
            } catch (Exception e) {
                if (e instanceof InterruptedException) Thread.currentThread().interrupt();
                Platform.runLater(() -> showStatus("Save failed: " + e.getMessage()));
            }
        }, "save-grades");
        worker.setDaemon(true);
        worker.start();
    }

    @FXML private void onDashboard() { SceneManager.goLecturerDashboard(); }
    @FXML private void onGroups()    { SceneManager.goGroups(); }
    @FXML private void onQuizCenter(){ SceneManager.goQuizManagement(); }
    @FXML private void onNewTopic()  { SceneManager.show("TopicCreation", "ACES — New Topic"); }

    @FXML private void onProfile() { SceneManager.goProfile(); }
    @FXML private void onLogout()     {
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

    public static class GradeRow {
        public long   userId;
        public String username;
        public int    replyCount;
        public double participationPct;
        public Double quizAvgPct;
        public int    quizCount;
        public double suggestedScore;
        public String score   = "";
        public String remarks = "";
    }
}