package forum.controllers;

import forum.api.ApiClient;
import forum.api.ApiException;
import forum.api.dto.GroupDto;
import forum.app.SceneManager;
import forum.app.Session;
import forum.app.ViewState;
import forum.models.Role;
import forum.models.User;

import javafx.application.Platform;
import javafx.fxml.FXML;
import javafx.geometry.Insets;
import javafx.scene.control.Button;
import javafx.scene.control.Label;
import javafx.scene.layout.FlowPane;
import javafx.scene.layout.HBox;
import javafx.scene.layout.Region;
import javafx.scene.layout.VBox;

import java.util.List;

public class GroupsIndexController {

    @FXML private Label     avatarLabel;
    @FXML private Label     userNameLabel;
    @FXML private Button    newGroupBtn;
    @FXML private FlowPane  groupsPane;
    @FXML private Label     statusLabel;

    private final ApiClient api = new ApiClient();

    @FXML
    private void initialize() {
        User u = Session.currentUser();
        if (u != null) {
            avatarLabel.setText(initials(u.displayName()));
            userNameLabel.setText(u.displayName());
            // Only lecturers and admins can create groups
            if (u.getRole() == Role.LECTURER || u.getRole() == Role.SYSTEM_ADMIN) {
                newGroupBtn.setManaged(true);
                newGroupBtn.setVisible(true);
            }
        }
        loadGroups();
    }

    private void loadGroups() {
        String token = Session.authToken();
        if (token == null) { showStatus("Offline — cannot load groups."); return; }

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
        Label name = new Label(g.name);
        name.getStyleClass().add("h-sm");
        name.setWrapText(true);

        Label members = new Label(g.memberCount + " members");
        members.getStyleClass().add("chip");

        HBox nameRow = new HBox(name, new Region() {{ HBox.setHgrow(this, javafx.scene.layout.Priority.ALWAYS); }}, members);
        nameRow.setAlignment(javafx.geometry.Pos.TOP_LEFT);

        Label desc = new Label(g.description != null ? g.description : "No description.");
        desc.getStyleClass().add("muted");
        desc.setWrapText(true);

        // View button
        Button viewBtn = new Button("View");
        viewBtn.getStyleClass().addAll("btn", "btn-secondary");
        viewBtn.setMaxWidth(Double.MAX_VALUE);
        HBox.setHgrow(viewBtn, javafx.scene.layout.Priority.ALWAYS);
        viewBtn.setOnAction(e -> openGroup(g));

        // Join / status button
        Button actionBtn = new Button();
        actionBtn.setMaxWidth(Double.MAX_VALUE);
        HBox.setHgrow(actionBtn, javafx.scene.layout.Priority.ALWAYS);

        if ("active".equals(g.myStatus)) {
            actionBtn.setText("✓ Joined");
            actionBtn.getStyleClass().addAll("badge", "badge-success");
            actionBtn.setDisable(true);
        } else if ("pending".equals(g.myStatus)) {
            actionBtn.setText("Pending");
            actionBtn.getStyleClass().addAll("badge", "badge-neutral");
            actionBtn.setDisable(true);
        } else {
            actionBtn.setText("Join");
            actionBtn.getStyleClass().addAll("btn", "btn-primary");
            actionBtn.setOnAction(e -> joinGroup(g, actionBtn));
        }

        HBox btnRow = new HBox(8, viewBtn, actionBtn);

        VBox card = new VBox(12, nameRow, desc, btnRow);
        card.getStyleClass().add("card");
        card.setPrefWidth(352);
        card.setPadding(new Insets(16));
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

    @FXML private void onNewGroup()  { SceneManager.show("GroupCreate", "Smart Discussion Forum — New Group"); }
    @FXML private void onDashboard() {
        User u = Session.currentUser();
        if (u != null) SceneManager.showHomeFor(u.getRole());
    }
    @FXML private void onForum()     { SceneManager.goForumDashboard(); }
    @FXML private void onGroups()    { SceneManager.goGroups(); }
    @FXML private void onMembers()   { SceneManager.goAdminMembers(); }
    @FXML private void onAnalytics() { SceneManager.goAdminAnalytics(); }
    @FXML private void onProfile()   { SceneManager.goProfile(); }
    @FXML private void onLogout()    {
        String token = Session.authToken();
        Session.end();
        new Thread(() -> new forum.services.AuthService().logout(token), "logout").start();
        SceneManager.show("Login", "Smart Discussion Forum");
    }

    private void showStatus(String msg) {
        statusLabel.setText(msg);
        statusLabel.setManaged(true);
        statusLabel.setVisible(true);
    }

    private String initials(String name) {
        if (name == null || name.isBlank()) return "?";
        return name.length() >= 2 ? name.substring(0, 2).toUpperCase() : name.toUpperCase();
    }
}