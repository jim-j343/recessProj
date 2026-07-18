package forum.controllers;

import forum.api.ApiClient;
import forum.api.ApiException;
import forum.app.SceneManager;
import forum.app.Session;
import forum.models.User;

import com.fasterxml.jackson.databind.JsonNode;
import com.fasterxml.jackson.databind.ObjectMapper;
import javafx.application.Platform;
import javafx.collections.FXCollections;
import javafx.collections.ObservableList;
import javafx.fxml.FXML;
import javafx.geometry.Pos;
import javafx.scene.control.*;
import javafx.scene.layout.HBox;
import javafx.scene.layout.VBox;

import java.net.URI;
import java.net.URLEncoder;
import java.net.http.HttpClient;
import java.net.http.HttpRequest;
import java.net.http.HttpResponse;
import java.nio.charset.StandardCharsets;
import java.time.Duration;
import java.util.ArrayList;
import java.util.List;

public class ParticipationGradingController {

    @FXML private Label     avatarLabel;
    @FXML private Label     userNameLabel;
    @FXML private Label     statusLabel;
    
    @FXML private ComboBox<TopicOption> topicCombo;
    @FXML private TextField searchField;
    @FXML private VBox studentListContainer;

    @FXML private javafx.scene.control.MenuButton notifButton;
    @FXML private Label notifBadge;

    private final ObservableList<GradeRow> rows = FXCollections.observableArrayList();
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
        if (notifButton != null) {
            forum.util.NavbarHelper.loadNotifications(api, notifButton, notifBadge);
        }

        topicCombo.getItems().add(new TopicOption(null, "All Topics"));
        topicCombo.setValue(topicCombo.getItems().get(0));

        // Auto-reload when topic changes
        topicCombo.valueProperty().addListener((obs, oldV, newV) -> {
            if (oldV == newV) return;
            if (oldV != null && newV != null && java.util.Objects.equals(oldV.id, newV.id)) return;
            loadStudents();
        });

        // Instant local filter when search text changes
        searchField.textProperty().addListener((obs, oldV, newV) -> {
            renderStudentRows();
        });

        loadStudents();
    }

    private void loadStudents() {
        String token = Session.authToken();
        if (token == null) { showStatus("Offline — cannot load students."); return; }

        TopicOption selectedTopic = topicCombo.getValue();
        String searchText = searchField.getText();

        Thread worker = new Thread(() -> {
            try {
                String topicQuery = "";
                if (selectedTopic != null && selectedTopic.id != null) {
                    topicQuery = "topic=" + selectedTopic.id + "&";
                }
                String searchQuery = "";
                if (searchText != null && !searchText.isBlank()) {
                    searchQuery = "search=" + URLEncoder.encode(searchText, StandardCharsets.UTF_8) + "&";
                }

                HttpClient http = HttpClient.newBuilder()
                        .connectTimeout(Duration.ofSeconds(8)).build();
                HttpRequest req = HttpRequest.newBuilder(
                        URI.create(forum.config.DatabaseConfig.API_BASE_URL + "/participation/grade-json?" + topicQuery + searchQuery))
                        .header("Authorization", "Bearer " + token)
                        .header("Accept", "application/json")
                        .GET().build();

                HttpResponse<String> resp = http.send(req, HttpResponse.BodyHandlers.ofString());

                if (resp.statusCode() == 200) {
                    ObjectMapper mapper = new ObjectMapper();
                    JsonNode root = mapper.readTree(resp.body());
                    
                    JsonNode rowsNode = root.has("rows") ? root.get("rows") : root;
                    List<GradeRow> result = new ArrayList<>();
                    for (JsonNode node : rowsNode) {
                        GradeRow row = new GradeRow();
                        row.userId           = node.get("user_id").asLong();
                        row.username         = node.get("username").asText();
                        row.postCount        = node.has("post_count") ? node.get("post_count").asInt() : 0;
                        row.replyCount       = node.get("reply_count").asInt();
                        row.participationPct = node.get("participation_pct").asDouble();
                        row.quizAvgPct       = node.hasNonNull("quiz_avg_pct") ? node.get("quiz_avg_pct").asDouble() : null;
                        row.quizCount        = node.get("quiz_count").asInt();
                        row.suggestedScore   = node.get("suggested_score").asDouble();
                        row.existingScore    = node.hasNonNull("existing_score") ? node.get("existing_score").asText() : null;
                        row.existingRemark   = node.hasNonNull("existing_remark") ? node.get("existing_remark").asText() : null;
                        row.score            = row.existingScore != null ? row.existingScore : String.valueOf(row.suggestedScore);
                        row.remarks          = row.existingRemark != null ? row.existingRemark : "";
                        result.add(row);
                    }
                    
                    List<TopicOption> parsedTopics = new ArrayList<>();
                    parsedTopics.add(new TopicOption(null, "All Topics"));
                    if (root.has("topics")) {
                        for (JsonNode node : root.get("topics")) {
                            parsedTopics.add(new TopicOption(node.get("topic_id").asLong(), node.get("title").asText()));
                        }
                    }

                    Platform.runLater(() -> {
                        rows.setAll(result);
                        
                        if (root.has("topics")) {
                            TopicOption current = topicCombo.getValue();
                            topicCombo.getItems().setAll(parsedTopics);
                            if (current != null && current.id != null) {
                                topicCombo.getItems().stream().filter(t -> t.id != null && t.id.equals(current.id)).findFirst().ifPresent(topicCombo::setValue);
                            } else {
                                topicCombo.setValue(topicCombo.getItems().get(0));
                            }
                        }
                        
                        renderStudentRows();
                    });
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

    private void renderStudentRows() {
        studentListContainer.getChildren().clear();
        String searchText = searchField.getText() == null ? "" : searchField.getText().trim().toLowerCase();

        for (GradeRow row : rows) {
            if (!searchText.isEmpty() && row.username != null && !row.username.toLowerCase().contains(searchText)) {
                continue; // Skip rendering this row if it doesn't match the search
            }

            HBox rowBox = new HBox(15);
            rowBox.setAlignment(Pos.CENTER_LEFT);
            rowBox.setStyle("-fx-padding: 16 16; -fx-border-color: transparent transparent #f3f4f6 transparent; -fx-border-width: 0 0 1 0; -fx-background-color: transparent;");

            HBox studentBox = new HBox(12);
            studentBox.setAlignment(Pos.CENTER_LEFT);
            studentBox.setPrefWidth(200);
            
            Label avatar = new Label(row.username.substring(0, 1).toUpperCase());
            avatar.setMinWidth(40); avatar.setMinHeight(40);
            avatar.setAlignment(Pos.CENTER);
            avatar.setStyle("-fx-background-color: #e0e7ff; -fx-text-fill: #4f46e5; -fx-font-weight: bold; -fx-font-size: 16px; -fx-background-radius: 20;");
            
            VBox nameBox = new VBox(2);
            Label nameLabel = new Label(row.username);
            nameLabel.setStyle("-fx-font-weight: 600; -fx-text-fill: #374151; -fx-font-size: 14px;");
            
            String scoreText = row.existingScore != null ? row.existingScore : "—";
            Label lastScoreLabel = new Label("Last score: " + scoreText);
            lastScoreLabel.setStyle("-fx-text-fill: #9ca3af; -fx-font-size: 12px;");
            nameBox.getChildren().addAll(nameLabel, lastScoreLabel);
            studentBox.getChildren().addAll(avatar, nameBox);

            Label repliesLabel = new Label(String.valueOf(row.replyCount));
            repliesLabel.setStyle("-fx-text-fill: #4b5563; -fx-font-size: 14px;");
            repliesLabel.setPrefWidth(80);

            String formattedPart = String.format("%.0f%%", row.participationPct);
            Label partLabel = new Label(formattedPart + " (reply-based, /10)");
            partLabel.setStyle("-fx-text-fill: #4b5563; -fx-font-size: 14px;");
            partLabel.setPrefWidth(140);

            Label testAvgLabel = new Label(row.quizAvgPct != null ? row.quizAvgPct + "% (" + row.quizCount + " quizzes)" : "No quizzes yet");
            testAvgLabel.setStyle("-fx-text-fill: #9ca3af; -fx-font-size: 14px;");
            testAvgLabel.setPrefWidth(140);

            VBox scoreBox = new VBox(4);
            scoreBox.setPrefWidth(120);
            TextField scoreField = new TextField(row.score);
            scoreField.setStyle("-fx-background-radius: 6; -fx-border-radius: 6; -fx-border-color: #d1d5db; -fx-background-color: white; -fx-padding: 8 12;");
            scoreField.textProperty().addListener((obs, oldV, newV) -> row.score = newV);
            Label suggestedLabel = new Label(row.suggestedScore + " Suggested - editable");
            suggestedLabel.setStyle("-fx-text-fill: #9ca3af; -fx-font-size: 10px;");
            scoreBox.getChildren().addAll(scoreField, suggestedLabel);

            TextField remarksField = new TextField(row.remarks);
            remarksField.setPromptText("Optional remark...");
            remarksField.setStyle("-fx-background-radius: 6; -fx-border-radius: 6; -fx-border-color: #d1d5db; -fx-background-color: white; -fx-padding: 8 12;");
            remarksField.setPrefWidth(220);
            remarksField.textProperty().addListener((obs, oldV, newV) -> row.remarks = newV);

            rowBox.getChildren().addAll(studentBox, repliesLabel, partLabel, testAvgLabel, scoreBox, remarksField);
            studentListContainer.getChildren().add(rowBox);
        }
        
        if (rows.isEmpty()) {
            Label empty = new Label("No students found.");
            empty.setStyle("-fx-text-fill: #9ca3af; -fx-padding: 32;");
            studentListContainer.getChildren().add(empty);
        }
    }

    @FXML private void onFilter() {
        loadStudents();
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
                Platform.runLater(() -> {
                    showStatus(resp.statusCode() == 200 ? "✓ Grades saved." : "Save failed — try again.");
                    if (resp.statusCode() == 200) {
                        loadStudents(); // Reload to get updated "Last score"
                    }
                });
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
        public int    postCount;
        public int    replyCount;
        public double participationPct;
        public Double quizAvgPct;
        public int    quizCount;
        public double suggestedScore;
        public String existingScore;
        public String existingRemark;
        public String score   = "";
        public String remarks = "";
    }

    public static class TopicOption {
        public final Long id;
        public final String title;
        public TopicOption(Long id, String title) { this.id = id; this.title = title; }
        @Override public String toString() { return title; }
    }
}