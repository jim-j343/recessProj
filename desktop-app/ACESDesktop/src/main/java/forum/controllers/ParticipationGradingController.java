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

    @FXML private Label     userNameLabel;
    @FXML private Label     statusLabel;
    @FXML private TableView<GradeRow> gradesTable;
    @FXML private TableColumn<GradeRow, String> colStudent;
    @FXML private TableColumn<GradeRow, String> colGroup;
    @FXML private TableColumn<GradeRow, String> colPosts;
    @FXML private TableColumn<GradeRow, String> colReplies;
    @FXML private TableColumn<GradeRow, String> colQuality;
    @FXML private TableColumn<GradeRow, String> colGrade;
    @FXML private TableColumn<GradeRow, String> colRemarks;

    private final ObservableList<GradeRow> rows = FXCollections.observableArrayList();
    private final ApiClient api = new ApiClient();

    @FXML
    private void initialize() {
        User u = Session.currentUser();
        if (u != null) userNameLabel.setText(u.displayName());

        colStudent.setCellValueFactory(c -> new SimpleStringProperty(c.getValue().username));
        colGroup.setCellValueFactory(c   -> new SimpleStringProperty(c.getValue().groupName));
        colPosts.setCellValueFactory(c   -> new SimpleStringProperty(String.valueOf(c.getValue().posts)));
        colReplies.setCellValueFactory(c -> new SimpleStringProperty(String.valueOf(c.getValue().replies)));
        colQuality.setCellValueFactory(c -> new SimpleStringProperty(c.getValue().quality));

        // Editable grade dropdown
        colGrade.setCellFactory(col -> new TableCell<>() {
            private final ComboBox<String> combo = new ComboBox<>(
                    FXCollections.observableArrayList("", "A", "B", "C", "D", "F"));
            {
                combo.setOnAction(e -> {
                    GradeRow row = getTableView().getItems().get(getIndex());
                    row.grade = combo.getValue();
                });
            }
            @Override protected void updateItem(String item, boolean empty) {
                super.updateItem(item, empty);
                if (empty) { setGraphic(null); return; }
                combo.setValue(getTableView().getItems().get(getIndex()).grade);
                setGraphic(combo);
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
                String baseUrl = forum.config.DatabaseConfig.API_BASE_URL;
                String apiUrl = baseUrl.endsWith("/api") ? baseUrl.substring(0, baseUrl.length() - 4) : baseUrl;
                String gradeJsonUrl = apiUrl + "/participation/grade-json";

                HttpClient http = HttpClient.newBuilder()
                        .connectTimeout(Duration.ofSeconds(8)).build();
                HttpRequest req = HttpRequest.newBuilder(URI.create(gradeJsonUrl))
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
                        if (node.has("user_id")) row.userId = node.get("user_id").asLong();
                        if (node.has("username")) row.username = node.get("username").asText();
                        if (node.has("group_name")) row.groupName = node.get("group_name").asText();
                        else row.groupName = "—";
                        if (node.has("post_count")) row.posts = node.get("post_count").asInt();
                        if (node.has("reply_count")) row.replies = node.get("reply_count").asInt();
                        if (node.has("quality")) row.quality = node.get("quality").asText();
                        if (node.has("existing_grade")) row.grade = node.get("existing_grade").asText("");
                        else row.grade = "";
                        row.remarks = "";
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
                    if (row.grade == null || row.grade.isBlank()) continue;
                    if (!first) json.append(",");
                    json.append("\"").append(row.userId).append("\":")
                            .append("{\"grade\":\"").append(row.grade).append("\",")
                            .append("\"remark\":\"").append(
                                    row.remarks != null ? row.remarks.replace("\"", "'") : "")
                            .append("\"}");
                    first = false;
                }
                json.append("}}");

                String baseUrl = forum.config.DatabaseConfig.API_BASE_URL;
                String apiUrl = baseUrl.endsWith("/api") ? baseUrl.substring(0, baseUrl.length() - 4) : baseUrl;
                String gradeUrl = apiUrl + "/participation/grade";

                HttpClient http = HttpClient.newBuilder()
                        .connectTimeout(Duration.ofSeconds(8)).build();
                HttpRequest req = HttpRequest.newBuilder(URI.create(gradeUrl))
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

    @FXML private void onDashboard()  { SceneManager.goLecturerDashboard(); }
    @FXML private void onGroups()     { SceneManager.goGroups(); }
    @FXML private void onQuizCenter() { SceneManager.goQuizManagement(); }

    private void showStatus(String msg) {
        statusLabel.setText(msg);
        statusLabel.setManaged(true);
        statusLabel.setVisible(true);
    }

    public static class GradeRow {
        public long   userId;
        public String username;
        public String groupName;
        public int    posts;
        public int    replies;
        public String quality;
        public String grade   = "";
        public String remarks = "";
    }
}
