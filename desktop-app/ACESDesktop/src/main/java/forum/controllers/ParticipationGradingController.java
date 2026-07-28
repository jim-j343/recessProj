package forum.controllers;

import java.net.URI;
import java.net.URLEncoder;
import java.net.http.HttpClient;
import java.net.http.HttpRequest;
import java.net.http.HttpResponse;
import java.nio.charset.StandardCharsets;
import java.time.Duration;
import java.util.ArrayList;
import java.util.List;

import com.fasterxml.jackson.databind.JsonNode;
import com.fasterxml.jackson.databind.ObjectMapper;

import forum.api.ApiClient;
import forum.app.SceneManager;
import forum.app.Session;
import forum.models.User;
import javafx.application.Platform;
import javafx.collections.FXCollections;
import javafx.collections.ObservableList;
import javafx.fxml.FXML;
import javafx.geometry.Pos;
import javafx.scene.control.ComboBox;
import javafx.scene.control.Label;
import javafx.scene.control.TextField;
import javafx.scene.layout.HBox;
import javafx.scene.layout.VBox;

public class ParticipationGradingController implements forum.app.Refreshable {

    @FXML private Label     avatarLabel;
    @FXML private Label     userNameLabel;
    @FXML private Label     statusLabel;
    
    @FXML private ComboBox<GroupOption> groupCombo;
    @FXML private Label courseLabel;
    @FXML private TextField searchField;
    @FXML private VBox studentListContainer;

    @FXML private javafx.scene.control.MenuButton notifButton;
    @FXML private Label notifBadge;

    private final ObservableList<GradeRow> rows = FXCollections.observableArrayList();
    private final ApiClient api = new ApiClient();

    /** Which course the visible numbers belong to — sent back when saving. */
    private Long selectedGroupId = null;
    /** Stops the combo's listener firing when we set its value after a load. */
    private boolean suppressReload = false;

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

        // Reload when the lecturer picks a different course. The guard stops
        // this firing again when we set the value programmatically after a
        // load, which would otherwise loop forever.
        groupCombo.valueProperty().addListener((obs, oldV, newV) -> {
            if (suppressReload) return;
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

    @Override
    public void refresh() {
        forum.models.User user = forum.app.Session.currentUser();
        if (user != null) {
            userNameLabel.setText(user.displayName());
            avatarLabel.setText(String.valueOf(user.displayName().trim().charAt(0)).toUpperCase());
        }
        forum.util.NavbarHelper.loadNotifications(api, notifButton, notifBadge);
        loadStudents();
    }

    private void loadStudents() {
        String token = Session.authToken();
        if (token == null) { showStatus("Offline — cannot load students."); return; }

        GroupOption selectedGroup = groupCombo.getValue();
        String searchText = searchField.getText();

        Thread worker = new Thread(() -> {
            try {
                String groupQuery = "";
                if (selectedGroup != null && selectedGroup.id != null) {
                    groupQuery = "group=" + selectedGroup.id + "&";
                }
                String searchQuery = "";
                if (searchText != null && !searchText.isBlank()) {
                    searchQuery = "search=" + URLEncoder.encode(searchText, StandardCharsets.UTF_8) + "&";
                }

                HttpClient http = HttpClient.newBuilder()
                        .connectTimeout(Duration.ofSeconds(8)).build();
                HttpRequest req = HttpRequest.newBuilder(
                        URI.create(forum.config.DatabaseConfig.API_BASE_URL + "/participation/grade-json?" + groupQuery + searchQuery))
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

                    // The lecturer's courses, and which one this data is for
                    List<GroupOption> parsedGroups = new ArrayList<>();
                    if (root.has("groups")) {
                        for (JsonNode node : root.get("groups")) {
                            parsedGroups.add(new GroupOption(
                                    node.get("group_id").asLong(),
                                    node.get("name").asText(),
                                    node.hasNonNull("course_name") ? node.get("course_name").asText() : null));
                        }
                    }

                    Long   serverGroupId = null;
                    String serverHeading = null;
                    if (root.hasNonNull("selected_group")) {
                        JsonNode sg = root.get("selected_group");
                        serverGroupId = sg.get("group_id").asLong();
                        serverHeading = sg.hasNonNull("course_name")
                                ? sg.get("course_name").asText()
                                : sg.get("name").asText();
                    }

                    final Long   groupId = serverGroupId;
                    final String heading = serverHeading;

                    Platform.runLater(() -> {
                        rows.setAll(result);
                        selectedGroupId = groupId;

                        if (heading != null && courseLabel != null) {
                            courseLabel.setText(heading);
                        }

                        if (!parsedGroups.isEmpty()) {
                            suppressReload = true;
                            groupCombo.getItems().setAll(parsedGroups);
                            if (groupId != null) {
                                groupCombo.getItems().stream()
                                        .filter(g -> g.id.equals(groupId))
                                        .findFirst()
                                        .ifPresent(groupCombo::setValue);
                            }
                            suppressReload = false;
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
                StringBuilder json = new StringBuilder();
                json.append("{");
                if (selectedGroupId != null) {
                    // Tell the server which course these marks belong to
                    json.append("\"group_id\":").append(selectedGroupId).append(",");
                }
                json.append("\"grades\":{");
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
        SceneManager.clearCache();
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

    public static class GroupOption {
        public final Long   id;
        public final String name;
        public final String courseName;

        public GroupOption(Long id, String name, String courseName) {
            this.id = id;
            this.name = name;
            this.courseName = courseName;
        }

        /** Shown in the dropdown — e.g. "BSE1206: Software Development Principles — BSSE Year 1" */
        @Override public String toString() {
            return courseName == null || courseName.isBlank() ? name : courseName + " — " + name;
        }
    }
}
