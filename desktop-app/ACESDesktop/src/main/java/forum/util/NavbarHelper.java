package forum.util;

import forum.api.ApiClient;
import forum.api.dto.NotificationDto;
import forum.app.SceneManager;
import forum.app.Session;

import javafx.application.Platform;
import javafx.geometry.Pos;
import javafx.scene.Cursor;
import javafx.scene.control.CustomMenuItem;
import javafx.scene.control.Label;
import javafx.scene.control.MenuItem;
import javafx.scene.control.MenuButton;
import javafx.scene.control.SeparatorMenuItem;
import javafx.scene.layout.HBox;
import javafx.scene.layout.Priority;
import javafx.scene.layout.Region;

import java.util.List;

/**
 * Shared utility that loads real notifications from the backend
 * and populates the notification MenuButton in any navbar.
 *
 * Usage in any controller's initialize():
 *   NavbarHelper.loadNotifications(api, notifButton, notifBadge);
 */
public class NavbarHelper {

    private NavbarHelper() {}

    /**
     * Fetches real notifications from the API on a background thread,
     * then updates the bell badge count and populates the dropdown items.
     *
     * @param api          ApiClient instance
     * @param notifButton  The bell MenuButton in the navbar
     * @param badgeLabel   The red circle Label showing the unread count
     */
    public static void loadNotifications(ApiClient api,
                                         MenuButton notifButton,
                                         Label badgeLabel) {
        String token = Session.authToken();
        if (token == null || token.isBlank()) return;

        Thread worker = new Thread(() -> {
            try {
                NotificationDto dto = api.fetchNotifications(token);
                Platform.runLater(() -> applyNotifications(dto, api, notifButton, badgeLabel));
            } catch (Exception e) {
                if (e instanceof InterruptedException) Thread.currentThread().interrupt();
                // Silently fail — notifications are non-critical
            }
        }, "load-notifications");
        worker.setDaemon(true);
        worker.start();
    }

    private static void applyNotifications(NotificationDto dto,
                                            ApiClient api,
                                            MenuButton notifButton,
                                            Label badgeLabel) {
        // Update badge visibility and count
        int count = dto.unreadCount;
        if (count > 0) {
            badgeLabel.setText(count > 9 ? "9+" : String.valueOf(count));
            badgeLabel.setManaged(true);
            badgeLabel.setVisible(true);
        } else {
            badgeLabel.setManaged(false);
            badgeLabel.setVisible(false);
        }

        // Rebuild dropdown items
        notifButton.getItems().clear();

        // Header row (interactive if count > 0)
        HBox headerBox = new HBox();
        headerBox.setAlignment(Pos.CENTER_LEFT);
        headerBox.setSpacing(16);
        Label titleLabel = new Label("Notifications");
        titleLabel.setStyle("-fx-font-weight: 600; -fx-text-fill: #374151; -fx-font-size: 14px;");
        Region spacer = new Region();
        HBox.setHgrow(spacer, Priority.ALWAYS);
        headerBox.getChildren().addAll(titleLabel, spacer);

        if (count > 0) {
            Label markAll = new Label("Mark all read");
            markAll.setStyle("-fx-text-fill: #4f46e5; -fx-font-size: 12px;");
            markAll.setCursor(Cursor.HAND);
            markAll.setOnMouseClicked(e -> {
                String tok = Session.authToken();
                if (tok == null) return;
                new Thread(() -> {
                    try {
                        api.markAllNotificationsRead(tok);
                        Platform.runLater(() -> loadNotifications(api, notifButton, badgeLabel));
                    } catch (Exception ignored) {}
                }, "mark-all-notif-read").start();
                notifButton.hide();
            });
            headerBox.getChildren().add(markAll);
        }

        CustomMenuItem headerItem = new CustomMenuItem(headerBox);
        headerItem.setHideOnClick(false);
        notifButton.getItems().add(headerItem);
        notifButton.getItems().add(new SeparatorMenuItem());

        List<NotificationDto.Item> items = dto.notifications;
        if (items == null || items.isEmpty()) {
            MenuItem empty = new MenuItem("You're all caught up.");
            empty.setStyle("-fx-text-fill: #9ca3af;");
            empty.setDisable(true);
            notifButton.getItems().add(empty);
        } else {
            for (NotificationDto.Item n : items) {
                String icon = iconFor(n.type);
                MenuItem item = new MenuItem(icon + "  " + n.message);
                item.setOnAction(e -> {
                    // Mark as read on background thread
                    String tok = Session.authToken();
                    if (tok != null) {
                        new Thread(() -> {
                            try { api.markNotificationRead(tok, n.notificationId); }
                            catch (Exception ignored) {}
                        }, "mark-notif-read").start();
                    }
                    // Reload after marking read
                    loadNotifications(api, notifButton, badgeLabel);
                });
                notifButton.getItems().add(item);
            }
        }

        // Separator + View all notifications
        notifButton.getItems().add(new SeparatorMenuItem());
        MenuItem viewAll = new MenuItem("View all notifications");
        viewAll.setStyle("-fx-text-fill: #4f46e5;");
        viewAll.setOnAction(e -> {
            Platform.runLater(() -> SceneManager.goNotifications());
        });
        notifButton.getItems().add(viewAll);
    }

    /** Maps notification type to a simple emoji icon (no image dependencies). */
    private static String iconFor(String type) {
        if (type == null) return "🔔";
        return switch (type) {
            case "reply", "mention"    -> "💬";
            case "warning"             -> "⚠️";
            case "blacklisted"         -> "🚫";
            case "quiz_announced"      -> "📝";
            default                    -> "🔔";
        };
    }
}
