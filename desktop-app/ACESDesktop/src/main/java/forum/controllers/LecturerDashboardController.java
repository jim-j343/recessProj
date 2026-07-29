package forum.controllers;

import forum.api.ApiClient;
import forum.api.ApiException;
import forum.api.dto.LecturerDashboardDto;
import forum.api.dto.QuizDto;
import forum.app.Refreshable;
import forum.app.SceneManager;
import forum.app.Session;
import forum.models.User;
import forum.services.AuthService;
import forum.util.NavbarHelper;
import forum.app.ViewState;

import java.time.format.DateTimeFormatter;
import java.time.ZonedDateTime;
import java.time.LocalDateTime;
import java.time.format.DateTimeParseException;

import javafx.application.Platform;
import javafx.fxml.FXML;
import javafx.geometry.Insets;
import javafx.scene.control.Label;
import javafx.scene.layout.HBox;
import javafx.scene.layout.Region;
import javafx.scene.layout.VBox;

import java.util.List;

public class LecturerDashboardController implements Refreshable {

    @FXML private Label avatarLabel;
    @FXML private Label userNameLabel;
    @FXML private Label quizCountLabel;
    @FXML private Label groupCountLabel;
    @FXML private Label topicCountLabel;
    @FXML private VBox  quizListBox;
    @FXML private Label noQuizzesLabel;

    @FXML private javafx.scene.control.MenuButton notifButton;
    @FXML private Label notifBadge;

    private final ApiClient api = new ApiClient();

    @FXML
    private void initialize() {
        User u = Session.currentUser();
        if (u != null) {
            avatarLabel.setText(initial(u.displayName()));
            userNameLabel.setText(u.displayName());
        }
        if (notifButton != null) {
            forum.util.NavbarHelper.loadNotifications(api, notifButton, notifBadge);
        }
        loadInBackground();
    }

    @Override
    public void refresh() {
        User u = Session.currentUser();
        if (u != null) {
            avatarLabel.setText(initial(u.displayName()));
            userNameLabel.setText(u.displayName());
        }
        if (notifButton != null) {
            forum.util.NavbarHelper.loadNotifications(api, notifButton, notifBadge);
        }
        loadInBackground();
    }

    private void loadInBackground() {
        String token = Session.authToken();
        if (token == null) return;

        Thread worker = new Thread(() -> {
            try {
                // Fetch dashboard metadata and the lecturer's quizzes explicitly.
                LecturerDashboardDto dashboard = api.getLecturerDashboard(token);
                java.util.List<QuizDto> myQuizzes = api.myQuizzes(token);

                Platform.runLater(() -> {
                    quizCountLabel.setText(String.valueOf(myQuizzes == null ? 0 : myQuizzes.size()));
                    groupCountLabel.setText(String.valueOf(dashboard.groupCount));
                    topicCountLabel.setText(String.valueOf(dashboard.topicCount));
                    renderQuizList(myQuizzes == null ? java.util.Collections.emptyList() : myQuizzes);
                });
            } catch (ApiException | java.io.IOException | InterruptedException e) {
                if (e instanceof InterruptedException) Thread.currentThread().interrupt();
                Platform.runLater(() -> {
                     quizCountLabel.setText("Error");
                     groupCountLabel.setText("Error");
                     topicCountLabel.setText("Error");
                     noQuizzesLabel.setText("Failed to load dashboard data.");
                     noQuizzesLabel.setManaged(true);
                     noQuizzesLabel.setVisible(true);
                     quizListBox.getChildren().clear();
                });
            }
        }, "lecturer-dashboard-load");
        worker.setDaemon(true);
        worker.start();
    }

    private void renderQuizList(List<QuizDto> quizzes) {
        quizListBox.getChildren().clear();
        if (quizzes.isEmpty()) {
            noQuizzesLabel.setManaged(true);
            noQuizzesLabel.setVisible(true);
            return;
        }

        boolean first = true;
        for (QuizDto q : quizzes) {
            if (!first) {
                Region div = new Region();
                div.getStyleClass().add("divider");
                div.setPrefHeight(1);
                quizListBox.getChildren().add(div);
            }
            quizListBox.getChildren().add(quizRow(q));
            first = false;
        }
    }

    private HBox quizRow(QuizDto q) {
        Label title = new Label(q.title);
        title.setStyle("-fx-font-size: 14px; -fx-font-weight: bold; -fx-text-fill: #111827;");

        String timeStr = "";
        if (q.startTime != null && !q.startTime.isBlank()) {
            try {
                ZonedDateTime zdt = ZonedDateTime.parse(q.startTime);
                timeStr = zdt.format(DateTimeFormatter.ofPattern("d MMM yyyy HH:mm"));
            } catch (DateTimeParseException e) {
                try {
                    LocalDateTime ldt = LocalDateTime.parse(q.startTime);
                    timeStr = ldt.format(DateTimeFormatter.ofPattern("d MMM yyyy HH:mm"));
                } catch (DateTimeParseException ex) {
                    timeStr = q.startTime;
                }
            }
            timeStr += " · " + q.durationMinutes + " mins";
        } else {
            timeStr = q.durationMinutes + " mins";
        }

        Label meta = new Label(timeStr);
        meta.setStyle("-fx-font-size: 12px; -fx-text-fill: #6b7280;");

        VBox info = new VBox(2, title, meta);
        HBox.setHgrow(info, javafx.scene.layout.Priority.ALWAYS);

        Label badge = new Label(q.isPublished ? "Published" : "Draft");
        badge.getStyleClass().addAll("badge", q.isPublished ? "badge-success" : "badge-neutral");

        HBox row = new HBox(info, badge);
        row.setAlignment(javafx.geometry.Pos.CENTER_LEFT);
        row.setPadding(new Insets(10, 0, 10, 0));

        // Make the whole row clickable to open quiz details
        row.setOnMouseClicked(evt -> {
            // If quiz is published — open a preview (lecturer view of quiz and results).
            if (q.isPublished) {
                ViewState.setSelectedQuiz(q);
                // Load detail + results and show preview screen
                new Thread(() -> {
                    try {
                        var detail = api.getQuiz(Session.authToken(), q.quizId);
                        ViewState.setSelectedQuizDetail(detail);
                        Platform.runLater(() -> SceneManager.show("QuizPreview", "ACES — Quiz"));
                    } catch (Exception e) {
                        e.printStackTrace();
                    }
                }, "load-quiz-preview").start();
            } else {
                // Unpublished — open the quiz management screen preloaded for editing
                ViewState.setSelectedQuiz(q);
                QuizManagementController ctl = SceneManager.showAndGetController("QuizManagement", "ACES — Edit Quiz");
                if (ctl != null) ctl.loadForEdit(q);
            }
        });

        row.setOnMouseEntered(e -> row.setStyle("-fx-cursor: hand; -fx-background-color: rgba(0,0,0,0.02);"));
        row.setOnMouseExited(e -> row.setStyle("") );

        return row;
    }

    @FXML private void onGroups()     { SceneManager.goGroups(); }
    @FXML private void onNewTopic()   { SceneManager.goTopicCreation(); }
    @FXML private void onForum()      { SceneManager.goForumDashboard(); }
    @FXML private void onQuizCenter() { SceneManager.goQuizManagement(); }
    @FXML private void onGrading()    { SceneManager.goParticipationGrading(); }

    @FXML private void onProfile() { forum.app.SceneManager.goProfile(); }

    @FXML
    private void onLogout() {
        String token = Session.authToken();
        Session.end();
        SceneManager.clearCache();
        new Thread(() -> new AuthService().logout(token), "logout").start();
        SceneManager.show("Login", "ACES");
    }

    /** Single first-letter initial — matches web x-avatar component. */
    private String initial(String name) {
        if (name == null || name.isBlank()) return "?";
        return String.valueOf(name.trim().charAt(0)).toUpperCase();
    }
}