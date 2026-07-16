package forum.controllers;

import forum.api.ApiClient;
import forum.api.ApiException;
import forum.api.dto.LecturerDashboardDto;
import forum.api.dto.QuizDto;
import forum.app.SceneManager;
import forum.app.Session;
import forum.models.User;
import forum.services.AuthService;
import forum.util.NavbarHelper;

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

public class LecturerDashboardController {

    @FXML private Label avatarLabel;
    @FXML private Label userNameLabel;
    @FXML private Label quizCountLabel;
    @FXML private Label groupCountLabel;
    @FXML private Label topicCountLabel;
    @FXML private VBox  quizListBox;
    @FXML private Label noQuizzesLabel;

    private final ApiClient api = new ApiClient();

    @FXML
    private void initialize() {
        User u = Session.currentUser();
        if (u != null) {
            avatarLabel.setText(initial(u.displayName()));
            userNameLabel.setText(u.displayName());
        }
        loadInBackground();
    }

    private void loadInBackground() {
        String token = Session.authToken();
        if (token == null) return;

        Thread worker = new Thread(() -> {
            try {
                LecturerDashboardDto dashboard = api.getLecturerDashboard(token);

                Platform.runLater(() -> {
                    quizCountLabel.setText(String.valueOf(dashboard.quizCount));
                    groupCountLabel.setText(String.valueOf(dashboard.groupCount));
                    topicCountLabel.setText(String.valueOf(dashboard.topicCount));
                    renderQuizList(dashboard.quizzes);
                });
            } catch (ApiException | java.io.IOException | InterruptedException e) {
                if (e instanceof InterruptedException) Thread.currentThread().interrupt();
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
        title.getStyleClass().add("label-strong");

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
        meta.getStyleClass().add("subtle");

        VBox info = new VBox(2, title, meta);
        HBox.setHgrow(info, javafx.scene.layout.Priority.ALWAYS);

        Label badge = new Label(q.isPublished ? "Published" : "Draft");
        badge.getStyleClass().addAll("badge", q.isPublished ? "badge-success" : "badge-neutral");

        HBox row = new HBox(info, badge);
        row.setAlignment(javafx.geometry.Pos.CENTER_LEFT);
        row.setPadding(new Insets(10, 0, 10, 0));
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
        new Thread(() -> new AuthService().logout(token), "logout").start();
        SceneManager.show("Login", "ACES");
    }

    /** Single first-letter initial — matches web x-avatar component. */
    private String initial(String name) {
        if (name == null || name.isBlank()) return "?";
        return String.valueOf(name.trim().charAt(0)).toUpperCase();
    }
}