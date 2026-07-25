package forum.controllers;

import forum.api.ApiClient;
import forum.api.ApiException;
import forum.api.dto.GroupDto;
import forum.app.SceneManager;
import forum.app.Session;
import forum.app.ViewState;
import forum.models.Role;
import forum.models.User;
import forum.util.NavbarHelper;

import javafx.application.Platform;
import javafx.fxml.FXML;
import javafx.geometry.Insets;
import javafx.scene.control.Button;
import javafx.scene.control.Label;
import javafx.scene.control.MenuButton;
import javafx.scene.layout.FlowPane;
import javafx.scene.layout.HBox;
import javafx.scene.layout.Region;
import javafx.scene.layout.VBox;

import java.util.List;

public class GroupsIndexController {

    @FXML private Label      avatarLabel;
    @FXML private Label      userNameLabel;
    @FXML private MenuButton notifButton;
    @FXML private Label      notifBadge;
    @FXML private Button     newGroupBtn;
    @FXML private FlowPane   groupsPane;
    @FXML private Label      statusLabel;
    @FXML private Label      navMyProgress;
    @FXML private Label      navNewTopic;
    @FXML private Label      navQuizCenter;
    @FXML private Label      navGrading;

    private final ApiClient api = new ApiClient();

    @FXML
    private void initialize() {
        User u = Session.currentUser();
        if (u != null) {
            avatarLabel.setText(initial(u.displayName()));
            userNameLabel.setText(u.displayName());
            // Only lecturers and admins can create groups
            if (u.getRole() == Role.LECTURER || u.getRole() == Role.SYSTEM_ADMIN) {
                newGroupBtn.setManaged(true);
                newGroupBtn.setVisible(true);
            }
            if (u.getRole() == Role.STUDENT && navMyProgress != null) {
                navMyProgress.setManaged(true); navMyProgress.setVisible(true);
            }
            if (u.getRole() != Role.SYSTEM_ADMIN && navNewTopic != null) {
                navNewTopic.setManaged(true); navNewTopic.setVisible(true);
            }
            if (u.getRole() == Role.LECTURER && navQuizCenter != null && navGrading != null) {
                navQuizCenter.setManaged(true); navQuizCenter.setVisible(true);
                navGrading.setManaged(true); navGrading.setVisible(true);
            }
        }
        NavbarHelper.loadNotifications(api, notifButton, notifBadge);
        loadGroups();
    }

    private void loadGroups() {
        String token = Session.authToken();
        if (token == null || token.isBlank()) {
            showStatus("Groups require an online session. Start the API, then log out and sign in again.");
            return;
        }

        Thread worker = new Thread(() -> {
            try {
                List<GroupDto> groups = api.listGroups(token);
                Platform.runLater(() -> renderGroups(groups));
            } catch (ApiException | java.io.IOException | InterruptedException e) {
                if (e instanceof InterruptedException) Thread.currentThread().interrupt();
                Platform.runLater(() -> showStatus("Could not load groups: " + e.getMessage()));
            }
        }, "load-groups");
        worker.setDaemon(true);
        worker.start();
    }

    private void renderGroups(List<GroupDto> groups) {
        groupsPane.getChildren().clear();
        if (groups.isEmpty()) { showStatus("No groups yet."); return; }

        for (GroupDto g : groups) {
            groupsPane.getChildren().add(groupCard(g));
        }
    }

    private VBox groupCard(GroupDto g) {
        // ── Title row: bold name + indigo member chip ──────────────────
        Label name = new Label(g.name);
        name.getStyleClass().add("h-sm");
        name.setWrapText(true);

        Label members = new Label(g.memberCount + " members");
        members.getStyleClass().add("chip");

        HBox nameRow = new HBox(8, name, new Region() {{ HBox.setHgrow(this, javafx.scene.layout.Priority.ALWAYS); }}, members);
        nameRow.setAlignment(javafx.geometry.Pos.CENTER_LEFT);

        // ── Description in indigo color (matching web link color) ──────
        Label desc = new Label(g.description != null && !g.description.isBlank()
                ? g.description : "No description provided.");
        desc.setStyle("-fx-text-fill: #4f46e5; -fx-font-size: 13px;");
        desc.setWrapText(true);

        // ── Topics count + Admin name row (muted gray, xs text) ────────
        Label topicsLbl = new Label(g.topicsCount + " topics");
        topicsLbl.setStyle("-fx-text-fill: #9ca3af; -fx-font-size: 12px;");

        Label adminLbl = new Label("Admin: " + (g.adminName != null ? g.adminName : "Unknown"));
        adminLbl.setStyle("-fx-text-fill: #9ca3af; -fx-font-size: 12px;");

        HBox metaRow = new HBox(topicsLbl,
                new Region() {{ HBox.setHgrow(this, javafx.scene.layout.Priority.ALWAYS); }},
                adminLbl);
        metaRow.setAlignment(javafx.geometry.Pos.CENTER_LEFT);

        // ── View button ────────────────────────────────────────────────
        Button viewBtn = new Button("View");
        viewBtn.getStyleClass().addAll("btn", "btn-secondary");
        viewBtn.setMaxWidth(Double.MAX_VALUE);
        HBox.setHgrow(viewBtn, javafx.scene.layout.Priority.ALWAYS);
        viewBtn.setOnAction(e -> openGroup(g));

        // ── Join / status button ───────────────────────────────────────
        Button actionBtn = new Button();
        actionBtn.setMaxWidth(Double.MAX_VALUE);
        HBox.setHgrow(actionBtn, javafx.scene.layout.Priority.ALWAYS);

        if ("active".equals(g.myStatus)) {
            actionBtn.setText("✓ Joined");
            // Green button matching web: bg-green-100 text-green-700
            actionBtn.setStyle("-fx-background-color: #dcfce7; -fx-text-fill: #15803d; " +
                    "-fx-font-weight: 600; -fx-font-size: 13px; -fx-background-radius: 6; " +
                    "-fx-border-radius: 6; -fx-padding: 9 18 9 18; -fx-cursor: hand;");
            actionBtn.setDisable(false);
        } else if ("pending".equals(g.myStatus)) {
            actionBtn.setText("Pending");
            actionBtn.getStyleClass().addAll("btn", "btn-secondary");
            actionBtn.setDisable(true);
        } else {
            actionBtn.setText("Join");
            actionBtn.getStyleClass().addAll("btn", "btn-primary");
            actionBtn.setOnAction(e -> joinGroup(g, actionBtn));
        }

        HBox btnRow = new HBox(8, viewBtn, actionBtn);

        VBox card = new VBox(12, nameRow, desc, metaRow, btnRow);
        card.getStyleClass().add("card");
        card.setPrefWidth(340);
        card.setPadding(new Insets(20));
        return card;
    }

    private void openGroup(GroupDto g) {
        ViewState.setSelectedGroup(g);
        SceneManager.goGroupShow();
    }

    private void joinGroup(GroupDto g, Button btn) {
        String token = Session.authToken();
        if (token == null) return;
        btn.setDisable(true);
        btn.setText("Joining...");
        Thread t = new Thread(() -> {
            try {
                api.joinGroup(token, g.groupId);
                Platform.runLater(() -> {
                    btn.setText("Pending");
                    btn.getStyleClass().removeAll("btn-primary");
                    btn.getStyleClass().add("badge-neutral");
                });
            } catch (ApiException ex) {
                Platform.runLater(() -> { btn.setDisable(false); btn.setText("Join"); });
            } catch (Exception ex) {
                if (ex instanceof InterruptedException) Thread.currentThread().interrupt();
                Platform.runLater(() -> { btn.setDisable(false); btn.setText("Join"); });
            }
        }, "join-group");
        t.setDaemon(true);
        t.start();
    }

    @FXML private void onNewGroup()  { SceneManager.show("GroupCreate", "ACES — New Group"); }
    @FXML private void onDashboard() {
        User u = Session.currentUser();
        if (u != null) SceneManager.showHomeFor(u.getRole());
    }
    @FXML private void onGroups()    { SceneManager.goGroups(); }
    @FXML private void onNewTopic()  { SceneManager.show("TopicCreation", "ACES — New Topic"); }
    @FXML private void onQuizCenter(){ SceneManager.goQuizManagement(); }
    @FXML private void onGrading()   { SceneManager.goParticipationGrading(); }
    @FXML private void onMembers()   { SceneManager.goAdminMembers(); }
    @FXML private void onAnalytics() { SceneManager.goAdminAnalytics(); }
    @FXML private void onMyProgress(){ SceneManager.goStudentAssessment(); }

    @FXML private void onModeration()  { SceneManager.goAdminModeration(); }
    @FXML private void onProfile()   { SceneManager.goProfile(); }
    @FXML private void onLogout()    {
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

    private String initial(String name) {
        if (name == null || name.isBlank()) return "?";
        return String.valueOf(name.trim().charAt(0)).toUpperCase();
    }
}
