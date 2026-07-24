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
import forum.util.NavbarHelper;

import com.fasterxml.jackson.databind.JsonNode;
import javafx.application.Platform;
import javafx.fxml.FXML;
import javafx.geometry.Insets;
import javafx.scene.control.Button;
import javafx.scene.control.Label;
import javafx.scene.control.MenuButton;
import javafx.scene.layout.HBox;
import javafx.scene.layout.Region;
import javafx.scene.layout.VBox;

import java.util.ArrayList;
import java.util.List;

public class GroupShowController {

    @FXML private Label      avatarLabel;
    @FXML private Label      userNameLabel;
    @FXML private MenuButton notifButton;
    @FXML private Label      notifBadge;
    @FXML private Label  groupNameLabel;
    @FXML private Label  groupDescLabel;
    @FXML private Label  memberCountLabel;
    @FXML private Label  courseNameLabel;
    @FXML private Label  topicCountLabel;
    @FXML private Label  warningDaysLabel;
    @FXML private Label  blacklistDaysLabel;

    @FXML private Button editGroupBtn;
    @FXML private Button deleteGroupBtn;
<<<<<<< HEAD
    @FXML private Button leaveGroupBtn;
=======
>>>>>>> c0a0fe073da5b40940d7bd0bb2ce0c10d655d5ed
    @FXML private Button addMemberBtn;
    
    @FXML private VBox   topicsBox;
    @FXML private Label  noTopicsLabel;

    @FXML private VBox   membersBox;
    @FXML private Label  pendingNote;
    @FXML private Label      navMyProgress;
    @FXML private Label      navNewTopic;
    @FXML private Label      navQuizCenter;
    @FXML private Label      navGrading;
    @FXML private Label      navMembers;
    @FXML private Label      navAnalytics;
    @FXML private Label      navModeration;
    
    // Add Member Modal
    @FXML private javafx.scene.layout.StackPane addMemberModal;
    @FXML private javafx.scene.control.TextField newMemberUsernameField;
    @FXML private javafx.scene.control.Label addMemberErrorLabel;
    @FXML private javafx.scene.control.Button confirmAddMemberBtn;

    // Delete Group Modal
    @FXML private javafx.scene.layout.StackPane deleteGroupModal;
    @FXML private javafx.scene.control.Label deleteGroupMessage;
    @FXML private javafx.scene.control.Label deleteGroupErrorLabel;
    @FXML private javafx.scene.control.Button confirmDeleteGroupBtn;

    // Remove Member Modal
    @FXML private javafx.scene.layout.StackPane removeMemberModal;
    @FXML private javafx.scene.control.TextField removeMemberReasonField;
    @FXML private javafx.scene.control.Label removeMemberErrorLabel;
    @FXML private javafx.scene.control.Button confirmRemoveMemberBtn;

    private long pendingRemoveUserId = -1;

    private final ApiClient api = new ApiClient();
    private final forum.database.TopicDao topicDao = new forum.database.TopicDao();
    private GroupDto group;

    @FXML
    private void initialize() {
        User u = Session.currentUser();
        if (u != null) {
            userNameLabel.setText(u.displayName());
            if (avatarLabel != null) {
                String name = u.displayName();
                avatarLabel.setText(name == null || name.isBlank() ? "?" : String.valueOf(name.trim().charAt(0)).toUpperCase());
            }
            if (u.getRole() == Role.SYSTEM_ADMIN && navMembers != null) {
                navMembers.setManaged(true); navMembers.setVisible(true);
                navAnalytics.setManaged(true); navAnalytics.setVisible(true);
                navModeration.setManaged(true); navModeration.setVisible(true);
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

        group = ViewState.getSelectedGroup();
        if (group == null) { SceneManager.goGroups(); return; }

        groupNameLabel.setText(group.name);
        courseNameLabel.setText(group.courseName != null ? group.courseName : "");
        groupDescLabel.setText(group.description != null ? group.description : "");
        memberCountLabel.setText(String.valueOf(group.memberCount));
        topicCountLabel.setText(String.valueOf(group.topicsCount));
        warningDaysLabel.setText(String.valueOf(group.warningDays));
        blacklistDaysLabel.setText(String.valueOf(group.blacklistDays));

        // Show buttons for group admin/lecturer
        boolean isAdmin = "admin".equals(group.myRole);
        boolean isLecturer = u != null && u.getRole() == Role.LECTURER;
        if (isAdmin || (isLecturer && group.adminId == (u != null ? u.getUserId() : -1))) {
            if (editGroupBtn != null) { editGroupBtn.setManaged(true); editGroupBtn.setVisible(true); }
            if (deleteGroupBtn != null) { deleteGroupBtn.setManaged(true); deleteGroupBtn.setVisible(true); }
            if (addMemberBtn != null) { addMemberBtn.setManaged(true); addMemberBtn.setVisible(true); }
        }

<<<<<<< HEAD
        // Any active member who isn't the group's admin can leave — mirrors
        // the @unless($group->admin_id === auth()->id()) check on web.
        boolean isActiveMember = "active".equals(group.myStatus);
        boolean isGroupAdmin = u != null && group.adminId == u.getUserId();
        if (leaveGroupBtn != null && isActiveMember && !isGroupAdmin) {
            leaveGroupBtn.setManaged(true);
            leaveGroupBtn.setVisible(true);
        }

=======
>>>>>>> c0a0fe073da5b40940d7bd0bb2ce0c10d655d5ed
        loadMembers();
        loadTopics();
    }

    private void loadTopics() {
        Thread worker = new Thread(() -> {
            List<forum.models.Topic> topics = topicDao.listRecentForGroup(group.groupId, 10);
            Platform.runLater(() -> renderTopics(topics));
        }, "load-group-topics");
        worker.setDaemon(true);
        worker.start();
    }

    private void renderTopics(List<forum.models.Topic> topics) {
        if (topicsBox == null) return;
        topicsBox.getChildren().clear();
        if (topics.isEmpty()) {
            if (noTopicsLabel != null) {
                noTopicsLabel.setManaged(true);
                noTopicsLabel.setVisible(true);
            }
            return;
        }

        for (forum.models.Topic t : topics) {
            VBox card = new VBox(8);
            card.setStyle("-fx-border-color: #e5e7eb; -fx-border-width: 0 0 1 0; -fx-padding: 12 0; -fx-cursor: hand;");
            
            HBox header = new HBox(8);
            VBox text = new VBox(2);
            Label title = new Label(t.getTitle());
            title.getStyleClass().add("label-strong");
            title.setWrapText(true);

            Label meta = new Label("by " + (t.getAuthorName() == null ? "Unknown" : t.getAuthorName()) + " · " + (t.getCreatedAt() != null ? t.getCreatedAt() : ""));
            meta.getStyleClass().add("subtle");
            meta.setStyle("-fx-font-size: 11px;");

            text.getChildren().addAll(title, meta);
            HBox.setHgrow(text, javafx.scene.layout.Priority.ALWAYS);
            
            Label tag = new Label(t.getCategory() != null ? t.getCategory() : "");
            tag.getStyleClass().addAll("badge", "badge-primary");
            tag.setStyle("-fx-font-size: 10px; -fx-padding: 2 6; -fx-background-radius: 12; -fx-background-color: #e0e7ff; -fx-text-fill: #4f46e5;");

            header.getChildren().addAll(text, tag);
            header.setAlignment(javafx.geometry.Pos.CENTER_LEFT);

            card.getChildren().add(header);
            card.setOnMouseClicked(e -> {
                ViewState.setSelectedTopic(t);
                SceneManager.show("TopicDetail", "ACES — " + t.getTitle());
            });

            topicsBox.getChildren().add(card);
        }
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
        Label av = new Label(initial(m.username));
        av.getStyleClass().addAll("avatar", "avatar-soft");
        av.setMinSize(32, 32);

        Label name = new Label(m.username);
        name.getStyleClass().add("label-strong");

        Label role = new Label(capitalize(m.role));
        role.getStyleClass().add("subtle");

        VBox info = new VBox(2, name, role);
        HBox.setHgrow(info, javafx.scene.layout.Priority.ALWAYS);

        HBox row = new HBox(12, av, info);
        row.setPadding(new Insets(8, 0, 8, 0));
        row.setAlignment(javafx.geometry.Pos.CENTER_LEFT);

        boolean isAdmin = "admin".equals(group.myRole);
        if (showApprove) {
            Button approveBtn = new Button("Approve");
            approveBtn.getStyleClass().addAll("btn", "btn-primary");
            approveBtn.setOnAction(e -> approve(m.userId, approveBtn));
            row.getChildren().add(approveBtn);
        } else if (isAdmin && !"admin".equals(m.role)) {
            Button removeBtn = new Button("Remove");
            removeBtn.setStyle("-fx-text-fill: #ef4444; -fx-background-color: transparent; -fx-cursor: hand;");
            removeBtn.setOnAction(e -> removeMember(m.userId, removeBtn));
            row.getChildren().add(removeBtn);
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

    @FXML private void onEditGroup() {
        if (group != null) {
            SceneManager.goGroupEdit(group);
        }
    }

    @FXML private void onDeleteGroup() {
        if (group == null) return;
        deleteGroupMessage.setText("Delete " + group.name + "?");
        deleteGroupErrorLabel.setVisible(false);
        deleteGroupErrorLabel.setManaged(false);
        confirmDeleteGroupBtn.setDisable(false);
        confirmDeleteGroupBtn.setText("Delete Group");
        deleteGroupModal.setVisible(true);
        deleteGroupModal.setManaged(true);
    }

    @FXML private void closeDeleteGroupModal() {
        if (deleteGroupModal != null) {
            deleteGroupModal.setVisible(false);
            deleteGroupModal.setManaged(false);
        }
    }

    @FXML private void confirmDeleteGroup() {
        if (group == null) return;
        String token = Session.authToken();
        if (token == null) return;
        
        confirmDeleteGroupBtn.setDisable(true);
        confirmDeleteGroupBtn.setText("Deleting...");
        deleteGroupErrorLabel.setVisible(false);
        deleteGroupErrorLabel.setManaged(false);
        
        Thread t = new Thread(() -> {
            try {
                api.deleteGroup(token, group.groupId);
                Platform.runLater(() -> SceneManager.goGroups());
            } catch (Exception e) {
                Platform.runLater(() -> {
                    confirmDeleteGroupBtn.setDisable(false);
                    confirmDeleteGroupBtn.setText("Delete Group");
                    deleteGroupErrorLabel.setText(e.getMessage());
                    deleteGroupErrorLabel.setVisible(true);
                    deleteGroupErrorLabel.setManaged(true);
                });
            }
        }, "delete-group-api");
        t.setDaemon(true);
        t.start();
    }

<<<<<<< HEAD
    @FXML private void onLeaveGroup() {
        if (group == null) return;
        javafx.scene.control.Alert confirm = new javafx.scene.control.Alert(
                javafx.scene.control.Alert.AlertType.CONFIRMATION,
                "Are you sure you want to leave this group?",
                javafx.scene.control.ButtonType.YES, javafx.scene.control.ButtonType.NO);
        confirm.setHeaderText(null);
        confirm.showAndWait().ifPresent(choice -> {
            if (choice != javafx.scene.control.ButtonType.YES) return;
            String token = Session.authToken();
            if (token == null) return;
            Thread t = new Thread(() -> {
                try {
                    api.leaveGroup(token, group.groupId);
                    Platform.runLater(SceneManager::goGroups);
                } catch (Exception e) {
                    Platform.runLater(() -> {
                        javafx.scene.control.Alert error = new javafx.scene.control.Alert(
                                javafx.scene.control.Alert.AlertType.ERROR, e.getMessage());
                        error.setHeaderText("Couldn't leave group");
                        error.showAndWait();
                    });
                }
            }, "leave-group-api");
            t.setDaemon(true);
            t.start();
        });
    }

=======
>>>>>>> c0a0fe073da5b40940d7bd0bb2ce0c10d655d5ed
    @FXML private void onAddMember() {
        if (group == null) return;
        newMemberUsernameField.clear();
        addMemberErrorLabel.setVisible(false);
        addMemberErrorLabel.setManaged(false);
        confirmAddMemberBtn.setDisable(false);
        confirmAddMemberBtn.setText("Add Member");
        addMemberModal.setVisible(true);
        addMemberModal.setManaged(true);
        newMemberUsernameField.requestFocus();
    }
    
    @FXML private void closeAddMemberModal() {
        if (addMemberModal != null) {
            addMemberModal.setVisible(false);
            addMemberModal.setManaged(false);
        }
    }
    
    @FXML private void confirmAddMember() {
        String username = newMemberUsernameField.getText().trim();
        if (username.isBlank()) {
            addMemberErrorLabel.setText("Please enter a username.");
            addMemberErrorLabel.setVisible(true);
            addMemberErrorLabel.setManaged(true);
            return;
        }
        
        String token = Session.authToken();
        if (token == null) return;
        
        confirmAddMemberBtn.setDisable(true);
        confirmAddMemberBtn.setText("Adding...");
        addMemberErrorLabel.setVisible(false);
        addMemberErrorLabel.setManaged(false);
        
        Thread t = new Thread(() -> {
            try {
                api.addMemberGroup(token, group.groupId, username);
                Platform.runLater(() -> {
                    closeAddMemberModal();
                    loadMembers();
                });
            } catch (Exception e) {
                Platform.runLater(() -> {
                    confirmAddMemberBtn.setDisable(false);
                    confirmAddMemberBtn.setText("Add Member");
                    addMemberErrorLabel.setText(e.getMessage());
                    addMemberErrorLabel.setVisible(true);
                    addMemberErrorLabel.setManaged(true);
                });
            }
        }, "add-member-api");
        t.setDaemon(true);
        t.start();
    }

    private void removeMember(long userId, Button btn) {
        if (group == null) return;
        pendingRemoveUserId = userId;
        removeMemberReasonField.clear();
        removeMemberErrorLabel.setVisible(false);
        removeMemberErrorLabel.setManaged(false);
        confirmRemoveMemberBtn.setDisable(false);
        confirmRemoveMemberBtn.setText("Remove");
        removeMemberModal.setVisible(true);
        removeMemberModal.setManaged(true);
        removeMemberReasonField.requestFocus();
    }

    @FXML private void closeRemoveMemberModal() {
        if (removeMemberModal != null) {
            removeMemberModal.setVisible(false);
            removeMemberModal.setManaged(false);
            pendingRemoveUserId = -1;
        }
    }

    @FXML private void confirmRemoveMember() {
        if (group == null || pendingRemoveUserId == -1) return;
        String reason = removeMemberReasonField.getText().trim();
        String token = Session.authToken();
        if (token == null) return;
        
        confirmRemoveMemberBtn.setDisable(true);
        confirmRemoveMemberBtn.setText("Removing...");
        removeMemberErrorLabel.setVisible(false);
        removeMemberErrorLabel.setManaged(false);
        
        Thread t = new Thread(() -> {
            try {
                api.removeMemberGroup(token, group.groupId, pendingRemoveUserId, reason);
                Platform.runLater(() -> {
                    closeRemoveMemberModal();
                    loadMembers();
                });
            } catch (Exception e) {
                Platform.runLater(() -> {
                    confirmRemoveMemberBtn.setDisable(false);
                    confirmRemoveMemberBtn.setText("Remove");
                    removeMemberErrorLabel.setText(e.getMessage());
                    removeMemberErrorLabel.setVisible(true);
                    removeMemberErrorLabel.setManaged(true);
                });
            }
        }, "remove-member-api");
        t.setDaemon(true);
        t.start();
    }

    @FXML private void onForum()     { SceneManager.goForumDashboard(); }
    @FXML private void onGroups()    { SceneManager.goGroups(); }
    @FXML private void onMembers()   { SceneManager.show("AdminGroupsIndex", "ACES — Manage Members"); } // Placeholder or real route if it exists
    @FXML private void onAnalytics() { SceneManager.show("AdminAnalytics", "ACES — Analytics"); }
    @FXML private void onModeration() { SceneManager.show("AdminModeration", "ACES — Moderation"); }
    @FXML private void onNewTopic()  { SceneManager.show("TopicCreation", "ACES — New Topic"); }
    @FXML private void onQuizCenter(){ SceneManager.goQuizManagement(); }
    @FXML private void onGrading()   { SceneManager.goParticipationGrading(); }
    @FXML private void onDashboard() {
        User u = Session.currentUser();
        if (u != null) SceneManager.showHomeFor(u.getRole());
    }
    @FXML private void onProfile()   { SceneManager.goProfile(); }
    @FXML private void onLogout()    {
        String token = Session.authToken();
        Session.end();
        new Thread(() -> new forum.services.AuthService().logout(token), "logout").start();
        SceneManager.show("Login", "ACES");
    }

    private String capitalize(String s) {
        if (s == null || s.isEmpty()) return s;
        return Character.toUpperCase(s.charAt(0)) + s.substring(1);
    }

    /** Single first-letter initial — matches web x-avatar component. */
    private String initial(String name) {
        if (name == null || name.isBlank()) return "?";
        return String.valueOf(name.trim().charAt(0)).toUpperCase();
    }

    private record MemberRow(long userId, String username, String role, String status) {}
}