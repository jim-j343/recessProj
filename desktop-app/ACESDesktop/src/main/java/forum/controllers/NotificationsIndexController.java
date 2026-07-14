package forum.controllers;

import forum.api.ApiClient;
import forum.api.dto.NotificationDto;
import forum.app.SceneManager;
import forum.app.Session;
import forum.models.User;
import forum.services.AuthService;
import forum.util.NavbarHelper;

import javafx.application.Platform;
import javafx.fxml.FXML;
import javafx.geometry.Insets;
import javafx.scene.control.Button;
import javafx.scene.control.Label;
import javafx.scene.control.MenuButton;
import javafx.scene.layout.HBox;
import javafx.scene.layout.Priority;
import javafx.scene.layout.Region;
import javafx.scene.layout.VBox;

import java.util.List;

public class NotificationsIndexController {

    @FXML private Label      avatarLabel;
    @FXML private Label      userNameLabel;
    @FXML private MenuButton notifButton;
    @FXML private Label      notifBadge;

    @FXML private Button     markAllReadBtn;
    @FXML private VBox       notificationsBox;
    @FXML private Label      loadingLabel;

    private final ApiClient api = new ApiClient();

    @FXML
    private void initialize() {
        User u = Session.currentUser();
        if (u != null) {
            if (avatarLabel != null)   avatarLabel.setText(initial(u.displayName()));
            if (userNameLabel != null) userNameLabel.setText(u.displayName());
        }

        NavbarHelper.loadNotifications(api, notifButton, notifBadge);
        loadAllNotifications();
    }

    private void loadAllNotifications() {
        String token = Session.authToken();
        if (token == null) return;

        Thread worker = new Thread(() -> {
            try {
                NotificationDto dto = api.fetchAllNotifications(token);
                Platform.runLater(() -> renderNotifications(dto));
            } catch (Exception e) {
                if (e instanceof InterruptedException) Thread.currentThread().interrupt();
                Platform.runLater(() -> {
                    loadingLabel.setText("Failed to load notifications.");
                });
            }
        }, "load-all-notifications");
        worker.setDaemon(true);
        worker.start();
    }

    private void renderNotifications(NotificationDto dto) {
        notificationsBox.getChildren().clear();

        if (dto.unreadCount > 0) {
            markAllReadBtn.setManaged(true);
            markAllReadBtn.setVisible(true);
        } else {
            markAllReadBtn.setManaged(false);
            markAllReadBtn.setVisible(false);
        }

        List<NotificationDto.Item> items = dto.notifications;
        if (items == null || items.isEmpty()) {
            Label empty = new Label("You don't have any notifications yet.");
            empty.setStyle("-fx-text-fill: #9ca3af; -fx-padding: 40;");
            empty.setMaxWidth(Double.MAX_VALUE);
            empty.setAlignment(javafx.geometry.Pos.CENTER);
            notificationsBox.getChildren().add(empty);
            return;
        }

        boolean first = true;
        for (NotificationDto.Item n : items) {
            if (!first) {
                Region div = new Region();
                div.getStyleClass().add("divider");
                div.setPrefHeight(1);
                notificationsBox.getChildren().add(div);
            }
            first = false;

            HBox row = new HBox(12);
            row.setPadding(new Insets(16));
            if (!n.isRead) {
                row.setStyle("-fx-background-color: rgba(79, 70, 229, 0.05); -fx-cursor: hand;");
            } else {
                row.setStyle("-fx-cursor: hand;");
            }

            Label iconLabel = new Label(iconFor(n.type));
            iconLabel.setStyle("-fx-font-size: 16px; -fx-text-fill: #9ca3af;");
            
            VBox textCol = new VBox(4);
            Label msgLabel = new Label(n.message);
            msgLabel.setWrapText(true);
            if (!n.isRead) {
                msgLabel.setStyle("-fx-font-weight: 500; -fx-text-fill: #111827;");
            } else {
                msgLabel.setStyle("-fx-text-fill: #4b5563;");
            }

            // A very simple parse for dates: e.g. 2026-07-14T12:00:00Z -> 2026-07-14 12:00
            String dateStr = n.createdAt != null ? n.createdAt.replace("T", " ").substring(0, Math.min(n.createdAt.length(), 16)) : "";
            Label dateLabel = new Label(dateStr);
            dateLabel.setStyle("-fx-font-size: 11px; -fx-text-fill: #9ca3af;");
            textCol.getChildren().addAll(msgLabel, dateLabel);
            HBox.setHgrow(textCol, Priority.ALWAYS);

            row.getChildren().addAll(iconLabel, textCol);

            if (!n.isRead) {
                Region dot = new Region();
                dot.setMinSize(8, 8);
                dot.setMaxSize(8, 8);
                dot.setStyle("-fx-background-color: #6366f1; -fx-background-radius: 4;");
                HBox.setMargin(dot, new Insets(6, 0, 0, 0));
                row.getChildren().add(dot);
            }

            row.setOnMouseClicked(e -> {
                if (!n.isRead) {
                    String tok = Session.authToken();
                    if (tok != null) {
                        new Thread(() -> {
                            try { api.markNotificationRead(tok, n.notificationId); }
                            catch (Exception ignored) {}
                            Platform.runLater(this::loadAllNotifications);
                            Platform.runLater(() -> NavbarHelper.loadNotifications(api, notifButton, notifBadge));
                        }, "mark-read").start();
                    }
                }
            });

            // Hover effects
            row.setOnMouseEntered(e -> {
                if (!n.isRead) row.setStyle("-fx-background-color: rgba(79, 70, 229, 0.10); -fx-cursor: hand;");
                else row.setStyle("-fx-background-color: #f9fafb; -fx-cursor: hand;");
            });
            row.setOnMouseExited(e -> {
                if (!n.isRead) row.setStyle("-fx-background-color: rgba(79, 70, 229, 0.05); -fx-cursor: hand;");
                else row.setStyle("-fx-background-color: transparent; -fx-cursor: hand;");
            });

            notificationsBox.getChildren().add(row);
        }
    }

    @FXML
    private void onMarkAllRead() {
        String tok = Session.authToken();
        if (tok == null) return;
        new Thread(() -> {
            try {
                api.markAllNotificationsRead(tok);
                Platform.runLater(() -> {
                    loadAllNotifications();
                    NavbarHelper.loadNotifications(api, notifButton, notifBadge);
                });
            } catch (Exception ignored) {}
        }, "mark-all-read").start();
    }

    @FXML private void onDashboard() {
        User u = Session.currentUser();
        if (u != null) SceneManager.showHomeFor(u.getRole());
    }

    @FXML private void onGroups()  { SceneManager.goGroups(); }
    @FXML private void onForum()   { SceneManager.goForumDashboard(); }
    @FXML private void onProfile() { SceneManager.goProfile(); }

    @FXML
    private void onLogout() {
        String token = Session.authToken();
        Session.end();
        new Thread(() -> new AuthService().logout(token), "logout").start();
        SceneManager.show("Login", "Smart Discussion Forum");
    }

    private String iconFor(String type) {
        if (type == null) return "🔔";
        return switch (type) {
            case "reply", "mention"    -> "💬";
            case "warning"             -> "⚠️";
            case "blacklisted"         -> "🚫";
            case "quiz_announced"      -> "📝";
            default                    -> "🔔";
        };
    }

    private String initial(String name) {
        if (name == null || name.isBlank()) return "?";
        return String.valueOf(name.trim().charAt(0)).toUpperCase();
    }
}
