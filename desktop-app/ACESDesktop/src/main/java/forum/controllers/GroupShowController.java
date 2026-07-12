package forum.controllers;

import forum.api.ApiClient;
import forum.api.ApiException;
import forum.api.dto.GroupDto;
import forum.api.dto.MemberDto;
import forum.app.SceneManager;
import forum.app.Session;
import forum.app.ViewState;
import forum.models.Role;
import forum.models.User;

import com.fasterxml.jackson.databind.JsonNode;
import javafx.application.Platform;
import javafx.fxml.FXML;
import javafx.geometry.Insets;
import javafx.scene.control.Button;
import javafx.scene.control.Label;
import javafx.scene.layout.HBox;
import javafx.scene.layout.Region;
import javafx.scene.layout.VBox;

import java.util.ArrayList;
import java.util.List;

public class GroupShowController {

    @FXML private Label  userNameLabel;
    @FXML private Label  groupNameLabel;
    @FXML private Label  groupDescLabel;
    @FXML private Label  memberCountLabel;
    @FXML private Label  myRoleLabel;
    @FXML private Label  myStatusLabel;
    @FXML private Button manageMembersBtn;
    @FXML private VBox   membersBox;
    @FXML private Label  pendingNote;

    private final ApiClient api = new ApiClient();
    private GroupDto group;

    @FXML
    private void initialize() {
        User u = Session.currentUser();
        if (u != null) userNameLabel.setText(u.displayName());

        group = ViewState.getSelectedGroup();
        if (group == null) { SceneManager.goGroups(); return; }

        groupNameLabel.setText(group.name);
        groupDescLabel.setText(group.description != null ? group.description : "");
        memberCountLabel.setText(String.valueOf(group.memberCount));
        myRoleLabel.setText(group.myRole != null ? capitalize(group.myRole) : "—");
        myStatusLabel.setText(group.myStatus != null ? capitalize(group.myStatus) : "Not joined");

        // Show manage members button for group admin/lecturer
        boolean isAdmin = "admin".equals(group.myRole);
        boolean isLecturer = u != null && u.getRole() == Role.LECTURER;
        if (isAdmin || (isLecturer && group.adminId == (u != null ? u.getUserId() : -1))) {
            manageMembersBtn.setManaged(true);
            manageMembersBtn.setVisible(true);
        }

        loadMembers();
    }

    private void loadMembers() {
        String token = Session.authToken();
        if (token == null) return;

        // Only admins can see the full member list via /members endpoint
        if (!"admin".equals(group.myRole)) return;

        Thread worker = new Thread(() -> {
            try {
                JsonNode root = api.groupMembers(token, group.groupId);
                List<MemberRow> active  = new ArrayList<>();
                List<MemberRow> pending = new ArrayList<>();

                JsonNode activeNode  = root.get("active");
                JsonNode pendingNode = root.get("pending");

                if (activeNode != null) {
                    for (JsonNode n : activeNode) {
                        active.add(new MemberRow(
                                n.get("user_id").asLong(),
                                n.get("username").asText(),
                                n.has("role") ? n.get("role").asText() : "member",
                                "active"));
                    }
                }
                if (pendingNode != null) {
                    for (JsonNode n : pendingNode) {
                        pending.add(new MemberRow(
                                n.get("user_id").asLong(),
                                n.get("username").asText(),
                                "member",
                                "pending"));
                    }
                }

                Platform.runLater(() -> renderMembers(active, pending));
            } catch (Exception e) {
                if (e instanceof InterruptedException) Thread.currentThread().interrupt();
            }
        }, "load-members");
        worker.setDaemon(true);
        worker.start();
    }

    private void renderMembers(List<MemberRow> active, List<MemberRow> pending) {
        membersBox.getChildren().clear();
        boolean first = true;
        for (MemberRow m : active) {
            if (!first) {
                Region div = new Region();
                div.getStyleClass().add("divider");
                div.setPrefHeight(1);
                membersBox.getChildren().add(div);
            }
            membersBox.getChildren().add(memberRow(m, false));
            first = false;
        }

        if (!pending.isEmpty()) {
            pendingNote.setText(pending.size() + " pending request(s) — use Manage Members to approve.");
            pendingNote.setManaged(true);
            pendingNote.setVisible(true);
        }
    }

    private HBox memberRow(MemberRow m, boolean showApprove) {
        Label name = new Label(m.username);
        name.getStyleClass().add("label-strong");

        Label role = new Label(capitalize(m.role));
        role.getStyleClass().add("subtle");

        VBox info = new VBox(2, name, role);
        HBox.setHgrow(info, javafx.scene.layout.Priority.ALWAYS);

        HBox row = new HBox(info);
        row.setPadding(new Insets(8, 0, 8, 0));
        row.setAlignment(javafx.geometry.Pos.CENTER_LEFT);

        if (showApprove) {
            Button approveBtn = new Button("Approve");
            approveBtn.getStyleClass().addAll("btn", "btn-primary");
            approveBtn.setOnAction(e -> approve(m.userId, approveBtn));
            row.getChildren().add(approveBtn);
        }
        return row;
    }

    private void approve(long userId, Button btn) {
        String token = Session.authToken();
        if (token == null) return;
        btn.setDisable(true);
        Thread t = new Thread(() -> {
            try {
                api.approveMember(token, group.groupId, userId);
                Platform.runLater(this::loadMembers);
            } catch (Exception e) {
                if (e instanceof InterruptedException) Thread.currentThread().interrupt();
                Platform.runLater(() -> btn.setDisable(false));
            }
        }, "approve-member");
        t.setDaemon(true);
        t.start();
    }

    @FXML private void onManageMembers() { loadMembers(); }
    @FXML private void onGroups()    { SceneManager.goGroups(); }
    @FXML private void onForum()     { SceneManager.goForumDashboard(); }
    @FXML private void onDashboard() {
        User u = Session.currentUser();
        if (u != null) SceneManager.showHomeFor(u.getRole());
    }

    private String capitalize(String s) {
        if (s == null || s.isEmpty()) return s;
        return Character.toUpperCase(s.charAt(0)) + s.substring(1);
    }

    private record MemberRow(long userId, String username, String role, String status) {}
}