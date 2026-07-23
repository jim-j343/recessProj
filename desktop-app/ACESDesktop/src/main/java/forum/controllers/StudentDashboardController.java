package forum.controllers;

import forum.api.ApiClient;
import forum.api.ApiException;
import forum.api.dto.QuizDto;
import forum.api.dto.QuizResultDto;
import forum.api.dto.StudentDashboardDto;
import forum.api.dto.StudentProgressDto;
import forum.app.SceneManager;
import forum.app.Session;
import forum.app.ViewState;
import forum.models.Role;
import forum.models.Topic;
import forum.models.User;
import forum.services.AuthService;
import forum.util.NavbarHelper;

import javafx.application.Platform;
import javafx.fxml.FXML;
import javafx.geometry.Insets;
import javafx.geometry.Pos;
import javafx.scene.control.Button;
import javafx.scene.control.Label;
import javafx.scene.control.MenuButton;
import javafx.scene.layout.HBox;
import javafx.scene.layout.Priority;
import javafx.scene.layout.Region;
import javafx.scene.layout.StackPane;
import javafx.scene.layout.VBox;

import java.time.OffsetDateTime;
import java.util.List;

public class StudentDashboardController {

    // ── Navbar ──────────────────────────────────────────────────────
    @FXML private Label      avatarLabel;
    @FXML private Label      userNameLabel;
    @FXML private MenuButton notifButton;
    @FXML private Label      notifBadge;

    // ── Page header ─────────────────────────────────────────────────
    @FXML private Label welcomeLabel;

    // ── Nav role links ───────────────────────────────────────────────
    @FXML private Label navMyProgress;
    @FXML private Label navNewTopic;

    // ── Left sidebar stats ───────────────────────────────────────────
    @FXML private Label groupCountLabel;
    @FXML private Label topicCountLabel;
    @FXML private Label postCountLabel;

    // ── Quiz alert banner ────────────────────────────────────────────
    @FXML private HBox  quizAlertBox;
    @FXML private Label quizAlertTitle;
    @FXML private Label quizAlertSub;
    @FXML private Label quizAlertBadge;
    @FXML private Button takeQuizBtn;

    // ── Progress cards ───────────────────────────────────────────────
    @FXML private Label     quizProgressLabel;
    @FXML private StackPane quizProgressBar;   // outer container (track + fill)
    @FXML private Region    quizProgressFill;  // the blue fill inside
    @FXML private Label     quizProgressSub;

    @FXML private VBox  participationByGroupBox;

    @FXML private Label standingLabel;
    @FXML private Label standingSub;

    // ── Grades ───────────────────────────────────────────────────────
    @FXML private Label averageGradeLabel;
    @FXML private VBox  resultsBox;
    @FXML private Label noResultsLabel;

    // ── Topic discussion card ────────────────────────────────────────
    @FXML private Label topicCardTitle;
    @FXML private Label topicCardMeta;
    @FXML private VBox  topicPostsBox;
    @FXML private Label viewDiscussionLink;
    @FXML private Button exportPdfBtn;

    // ── Right sidebar ────────────────────────────────────────────────
    @FXML private Label recommendedLabel;
    @FXML private Label recommendedMeta;

    private final ApiClient api = new ApiClient();

    // ───────────────────────────────────────────────────────────────
    //  Initialise
    // ───────────────────────────────────────────────────────────────
    @FXML
    private void initialize() {
        User u = Session.currentUser();
        if (u != null) {
            // Avatar initial + username — matches web exactly
            avatarLabel.setText(initial(u.displayName()));
            userNameLabel.setText(u.displayName());

            // Welcome message — exact match to web
            welcomeLabel.setText("Welcome back, " + u.displayName());

            // Show/hide nav links by role
            if (u.getRole() == Role.STUDENT && navMyProgress != null) {
                navMyProgress.setManaged(true);
                navMyProgress.setVisible(true);
            }
            if (u.getRole() != Role.SYSTEM_ADMIN && navNewTopic != null) {
                navNewTopic.setManaged(true);
                navNewTopic.setVisible(true);
            }
        }

        // Load notifications into bell dropdown
        if (notifButton != null) {
            NavbarHelper.loadNotifications(api, notifButton, notifBadge);
        }

        // Hide the "Enter Quiz" button until we confirm a live quiz
        if (takeQuizBtn != null) {
            takeQuizBtn.setManaged(false);
            takeQuizBtn.setVisible(false);
        }

        // Kick off background data loads
        loadSidebarStats();
        loadQuizData();
        loadDashboardExtras();
    }

    // ───────────────────────────────────────────────────────────────
    //  1. Sidebar counts  (groups, topics, posts)
    // ───────────────────────────────────────────────────────────────
    private void loadSidebarStats() {
        String token = Session.authToken();
        if (token == null) return;

        new Thread(() -> {
            try {
                // groups count
                int groups = api.listGroups(token).size();
                // topics count (topics visible in all my groups)
                int topics = api.listTopics(token).size();
                // post count from progress endpoint
                StudentProgressDto prog = api.studentProgress(token);
                int posts = prog.postCount;

                Platform.runLater(() -> {
                    if (groupCountLabel != null) groupCountLabel.setText(String.valueOf(groups));
                    if (topicCountLabel != null) topicCountLabel.setText(String.valueOf(topics));
                    if (postCountLabel  != null) postCountLabel .setText(String.valueOf(posts));
                });
            } catch (Exception e) {
                if (e instanceof InterruptedException) Thread.currentThread().interrupt();
                // Non-critical — sidebar shows "—" on failure; ignore silently
            }
        }, "sidebar-stats").start();
    }

    // ───────────────────────────────────────────────────────────────
    //  2. Quiz data  (alert banner + progress card + grades)
    // ───────────────────────────────────────────────────────────────
    private void loadQuizData() {
        String token = Session.authToken();
        if (token == null) return;

        new Thread(() -> {
            try {
                List<QuizDto> quizzes = api.listQuizzes(token);

                // ── Determine active / upcoming quiz ─────────────────
                QuizDto active   = null;
                QuizDto upcoming = null;
                OffsetDateTime now = OffsetDateTime.now();

                for (QuizDto q : quizzes) {
                    if (!q.isPublished || q.startTime == null) continue;
                    try {
                        OffsetDateTime start = OffsetDateTime.parse(q.startTime);
                        OffsetDateTime end   = start.plusMinutes(q.durationMinutes);
                        if (!now.isBefore(start) && now.isBefore(end)) {
                            active = q;
                        } else if (now.isBefore(start)) {
                            if (upcoming == null || start.isBefore(OffsetDateTime.parse(upcoming.startTime))) {
                                upcoming = q;
                            }
                        }
                    } catch (Exception ignored) {}
                }

                final QuizDto finalActive   = active;
                final QuizDto finalUpcoming = upcoming;

                // ── Quiz progress  (completed / total) ───────────────
                int total     = quizzes.size();
                int completed = 0;

                if (resultsBox != null) {
                    Platform.runLater(() -> resultsBox.getChildren().clear());
                }

                for (QuizDto q : quizzes) {
                    try {
                        QuizResultDto result = api.myQuizResult(token, q.quizId);
                        completed++;
                        final QuizDto     fq = q;
                        final QuizResultDto fr = result;
                        Platform.runLater(() -> addResultRow(fq, fr));
                    } catch (ApiException ex) {
                        // 404 = not submitted yet — expected, skip
                    }
                }

                final int fCompleted = completed;
                final int fTotal     = total;

                Platform.runLater(() -> {
                    // Quiz alert banner
                    renderQuizAlert(finalActive, finalUpcoming);

                    // Progress card
                    if (quizProgressLabel != null) {
                        quizProgressLabel.setText(fCompleted + "/" + fTotal);
                    }
                    if (quizProgressFill != null && quizProgressBar != null) {
                        final double ratio = fTotal > 0 ? (double) fCompleted / fTotal : 0;
                        // StackPane respects maxWidth to constrain child size;
                        // bind both prefWidth AND maxWidth so the bar fills correctly.
                        quizProgressFill.prefWidthProperty().bind(
                            quizProgressBar.widthProperty().multiply(Math.max(0, Math.min(1, ratio))));
                        quizProgressFill.maxWidthProperty().bind(
                            quizProgressBar.widthProperty().multiply(Math.max(0, Math.min(1, ratio))));
                    }
                    if (quizProgressSub != null) {
                        if (fTotal > 0) {
                            int pct = (int) Math.round((double) fCompleted / fTotal * 100);
                            quizProgressSub.setText(pct + "% of published quizzes completed");
                        } else {
                            quizProgressSub.setText("No published quizzes yet.");
                        }
                    }
                    // Show "no grades" placeholder if nothing came back
                    if (noResultsLabel != null) {
                        boolean empty = (resultsBox == null || resultsBox.getChildren().isEmpty());
                        noResultsLabel.setManaged(empty);
                        noResultsLabel.setVisible(empty);
                    }
                    // Average grade badge
                    if (averageGradeLabel != null) {
                        if (fCompleted > 0 && resultsBox != null) {
                            // Average is computed in addResultRow and stored temporarily;
                            // recompute here from the label texts (simpler than threading state)
                            // — we refresh via renderAverageGrade() below
                        }
                    }
                });

            } catch (ApiException | java.io.IOException | InterruptedException e) {
                if (e instanceof InterruptedException) Thread.currentThread().interrupt();
            }
        }, "quiz-data-load").start();
    }

    // ───────────────────────────────────────────────────────────────
    //  3. Dashboard extras  (participation, standing, topic card)
    // ───────────────────────────────────────────────────────────────
    private void loadDashboardExtras() {
        String token = Session.authToken();
        if (token == null) return;

        new Thread(() -> {
            try {
                StudentDashboardDto dto = api.studentDashboard(token);
                Platform.runLater(() -> renderExtras(dto));
            } catch (ApiException | java.io.IOException | InterruptedException e) {
                if (e instanceof InterruptedException) Thread.currentThread().interrupt();
            }
        }, "dashboard-extras").start();
    }

    // ───────────────────────────────────────────────────────────────
    //  Render helpers (always called on FX thread)
    // ───────────────────────────────────────────────────────────────

    /** Renders the quiz alert banner — three states: active, upcoming, none. */
    private void renderQuizAlert(QuizDto active, QuizDto upcoming) {
        if (quizAlertBox == null) return;

        if (active != null) {
            // ── Live quiz  (amber) ──────────────────────────────────
            quizAlertBox.setStyle(
                "-fx-padding: 16 20; -fx-background-color: #fffbeb; " +
                "-fx-border-color: #f59e0b; -fx-border-radius: 8; " +
                "-fx-background-radius: 8; -fx-border-width: 1 1 1 4;");
            if (quizAlertTitle != null) quizAlertTitle.setText("Live Quiz In Progress");
            if (quizAlertSub   != null) quizAlertSub.setText(
                "\u201c" + active.title + "\u201d is open now (" + active.durationMinutes + " min). " +
                "It will auto-submit when time expires.");
            if (quizAlertBadge != null) {
                quizAlertBadge.setText("Enter now");
                quizAlertBadge.setStyle(
                    "-fx-background-color: #fef3c7; -fx-text-fill: #b45309; " +
                    "-fx-padding: 6 12; -fx-background-radius: 16; " +
                    "-fx-font-size: 12px; -fx-font-weight: bold;");
                quizAlertBadge.setManaged(true);
                quizAlertBadge.setVisible(true);
            }
            if (takeQuizBtn != null) {
                takeQuizBtn.setManaged(true);
                takeQuizBtn.setVisible(true);
                ViewState.setSelectedQuiz(active);
            }

        } else if (upcoming != null) {
            // ── Upcoming quiz  (indigo) ─────────────────────────────
            quizAlertBox.setStyle(
                "-fx-padding: 16 20; -fx-background-color: #f8fafc; " +
                "-fx-border-color: #818cf8; -fx-border-radius: 8; " +
                "-fx-background-radius: 8; -fx-border-width: 1 1 1 4;");
            if (quizAlertTitle != null) quizAlertTitle.setText("Upcoming Quiz");

            String timeLabel = "soon";
            try {
                OffsetDateTime start = OffsetDateTime.parse(upcoming.startTime);
                long mins = java.time.Duration.between(OffsetDateTime.now(), start).toMinutes();
                if (mins < 60) {
                    timeLabel = "in " + mins + " min";
                } else if (mins < 1440) {
                    timeLabel = "in " + (mins / 60) + "h";
                } else {
                    timeLabel = "in " + (mins / 1440) + "d";
                }
                String formatted = start.format(
                    java.time.format.DateTimeFormatter.ofPattern("EEE, dd MMM yyyy \u00B7 HH:mm"));
                if (quizAlertSub != null)
                    quizAlertSub.setText("\u201c" + upcoming.title + "\u201d opens " + formatted
                        + " (" + upcoming.durationMinutes + " min).");
            } catch (Exception ex) {
                if (quizAlertSub != null)
                    quizAlertSub.setText("\u201c" + upcoming.title + "\u201d opens soon.");
            }

            if (quizAlertBadge != null) {
                quizAlertBadge.setText("Starts " + timeLabel);
                quizAlertBadge.setStyle(
                    "-fx-background-color: #e0e7ff; -fx-text-fill: #4338ca; " +
                    "-fx-padding: 6 12; -fx-background-radius: 16; " +
                    "-fx-font-size: 12px; -fx-font-weight: bold;");
                quizAlertBadge.setManaged(true);
                quizAlertBadge.setVisible(true);
            }

        } else {
            // ── No quizzes pending  (gray) ──────────────────────────
            quizAlertBox.setStyle(
                "-fx-padding: 16 20; -fx-background-color: #f9fafb; " +
                "-fx-border-color: #d1d5db; -fx-border-radius: 8; " +
                "-fx-background-radius: 8; -fx-border-width: 1 1 1 4;");
            if (quizAlertTitle != null) quizAlertTitle.setText("No Quizzes Pending");
            if (quizAlertSub   != null) quizAlertSub.setText(
                "You\u2019re all caught up \u2014 nothing scheduled for your groups right now.");
            if (quizAlertBadge != null) {
                quizAlertBadge.setManaged(false);
                quizAlertBadge.setVisible(false);
            }
        }
    }

    /** Renders participation, community standing, and the topic discussion card. */
    private void renderExtras(StudentDashboardDto dto) {

        // ── Participation by group ──────────────────────────────────
        if (participationByGroupBox != null && dto.participationByGroup != null) {
            participationByGroupBox.getChildren().clear();
            if (dto.participationByGroup.isEmpty()) {
                Label empty = new Label("No group data yet.");
                empty.setStyle("-fx-font-size: 12px; -fx-text-fill: #9ca3af;");
                participationByGroupBox.getChildren().add(empty);
            } else {
                for (StudentDashboardDto.ParticipationByGroup g : dto.participationByGroup) {
                    HBox row = new HBox();
                    row.setAlignment(Pos.CENTER_LEFT);

                    Label name = new Label(g.groupName);
                    name.setStyle("-fx-font-size: 12px; -fx-text-fill: #6b7280;");
                    name.setMaxWidth(Double.MAX_VALUE);
                    HBox.setHgrow(name, Priority.ALWAYS);

                    Label pct = new Label((int) Math.round(g.pct) + "%");
                    pct.setStyle("-fx-font-size: 12px; -fx-font-weight: bold; -fx-text-fill: #111827;");

                    row.getChildren().addAll(name, pct);
                    participationByGroupBox.getChildren().add(row);
                }
            }
        }

        // ── Community standing ──────────────────────────────────────
        if (standingLabel != null && dto.standing != null) {
            standingLabel.getStyleClass().removeAll("badge-success", "badge-warning", "badge-danger");
            boolean isWarning = "warning".equals(dto.standing.status);

            String badgeText = dto.standing.label != null
                ? dto.standing.label : (isWarning ? "Warning" : "Good Standing");
            standingLabel.setText(badgeText);

            if (isWarning) {
                standingLabel.setStyle(
                    "-fx-background-color: #fef3c7; -fx-text-fill: #b45309; " +
                    "-fx-padding: 4 12; -fx-background-radius: 16; " +
                    "-fx-font-weight: bold; -fx-font-size: 14px;");
            } else {
                standingLabel.setStyle(
                    "-fx-background-color: #dcfce7; -fx-text-fill: #15803d; " +
                    "-fx-padding: 4 12; -fx-background-radius: 16; " +
                    "-fx-font-weight: bold; -fx-font-size: 14px;");
            }
        }

        if (standingSub != null && dto.standing != null && dto.standing.sub != null) {
            standingSub.setText(dto.standing.sub);
        }

        // ── Latest topic card ───────────────────────────────────────
        if (dto.latestTopic != null) {
            if (topicCardTitle != null)
                topicCardTitle.setText("Topic: " + dto.latestTopic.title);
            if (topicCardMeta != null)
                topicCardMeta.setText(dto.latestTopic.groupName
                    + " \u00B7 " + dto.latestTopic.postsCount + " replies"
                    + " \u00B7 " + dto.latestTopic.createdAtHuman);
            if (viewDiscussionLink != null) {
                viewDiscussionLink.setOnMouseClicked(e -> openTopic(dto.latestTopic));
            }
            if (exportPdfBtn != null) {
                exportPdfBtn.setUserData(dto.latestTopic.topicId);
                exportPdfBtn.setManaged(true);
                exportPdfBtn.setVisible(true);
            }
            // Load real posts dynamically
            loadTopicPosts(dto.latestTopic.topicId);
        } else {
            if (topicCardTitle != null)
                topicCardTitle.setText("No topics yet in your groups");
            if (topicCardMeta != null)
                topicCardMeta.setText("Join a group and start a discussion.");
        }

        // ── Recommended topic ───────────────────────────────────────
        if (recommendedLabel != null) {
            if (dto.recommendedTopic != null) {
                recommendedLabel.setText("# " + dto.recommendedTopic.title);
                recommendedLabel.setStyle("-fx-text-fill: #4f46e5; -fx-font-size: 12px; -fx-font-weight: 600; -fx-cursor: hand;");
                recommendedLabel.setOnMouseClicked(e -> openTopic(dto.recommendedTopic));
                if (recommendedMeta != null) {
                    recommendedMeta.setManaged(false);
                    recommendedMeta.setVisible(false);
                }
            } else {
                recommendedLabel.setText("No recommendations yet.");
                recommendedLabel.setStyle("-fx-text-fill: #6366f1; -fx-font-size: 12px;");
            }
        }
    }

    /** Adds a grade row for a completed quiz. Dynamically builds rows like the web's forelse. */
    private void addResultRow(QuizDto quiz, QuizResultDto result) {
        if (resultsBox == null) return;

        int pct = result.total > 0
            ? (int) Math.round((result.score / result.total) * 100) : 0;

        // Divider between rows (skipped for first row)
        if (!resultsBox.getChildren().isEmpty()) {
            Region div = new Region();
            div.setStyle("-fx-background-color: #f9fafb; -fx-min-height: 1; -fx-pref-height: 1; -fx-max-height: 1;");
            resultsBox.getChildren().add(div);
        }

        HBox row = new HBox();
        row.setAlignment(Pos.CENTER_LEFT);
        row.setPadding(new Insets(12, 0, 12, 0));

        VBox left = new VBox(2);
        HBox.setHgrow(left, Priority.ALWAYS);

        Label titleLabel = new Label(quiz.title);
        titleLabel.setStyle("-fx-font-weight: 500; -fx-text-fill: #1f2937; -fx-font-size: 14px;");

        Label dateLabel = new Label(result.submittedAt != null
            ? formatSubmittedAt(result.submittedAt) : "Submitted");
        dateLabel.setStyle("-fx-font-size: 12px; -fx-text-fill: #9ca3af;");

        left.getChildren().addAll(titleLabel, dateLabel);

        Label scoreLabel = new Label(pct + "%");
        scoreLabel.setStyle("-fx-font-weight: bold; -fx-text-fill: #111827; -fx-font-size: 14px;");

        row.getChildren().addAll(left, scoreLabel);
        resultsBox.getChildren().add(row);

        // Refresh average grade badge
        refreshAverageBadge();
    }

    /** Recomputes and updates the Average grade label from all visible score labels. */
    private void refreshAverageBadge() {
        if (averageGradeLabel == null || resultsBox == null) return;
        double sum = 0;
        int count = 0;
        for (var node : resultsBox.getChildren()) {
            if (node instanceof HBox row) {
                for (var child : row.getChildren()) {
                    if (child instanceof Label lbl) {
                        String txt = lbl.getText();
                        if (txt != null && txt.endsWith("%") && !txt.contains(" ")) {
                            try {
                                sum += Double.parseDouble(txt.replace("%", ""));
                                count++;
                            } catch (NumberFormatException ignored) {}
                        }
                    }
                }
            }
        }
        if (count > 0) {
            averageGradeLabel.setText("Average: " + Math.round(sum / count) + "%");
            averageGradeLabel.setManaged(true);
            averageGradeLabel.setVisible(true);
        }
    }

    /** Formats an ISO-8601 submitted_at string into "d MMM yyyy, HH:mm". */
    private String formatSubmittedAt(String iso) {
        try {
            OffsetDateTime dt = OffsetDateTime.parse(iso);
            return "Submitted " + dt.format(
                java.time.format.DateTimeFormatter.ofPattern("dd MMM yyyy, HH:mm"));
        } catch (Exception e) {
            return "Submitted";
        }
    }

    private void loadTopicPosts(long topicId) {
        String token = Session.authToken();
        if (token == null) return;
        new Thread(() -> {
            try {
                var detail = api.getTopic(token, topicId);
                java.util.List<forum.api.dto.PostDto> posts = detail.posts;
                if (posts != null && !posts.isEmpty()) {
                    // Get latest 3 posts (sort desc, take 3, then sort asc to display chronologically)
                    java.util.List<forum.api.dto.PostDto> latest3 = new java.util.ArrayList<>(posts);
                    latest3.sort((p1, p2) -> p2.created_at.compareTo(p1.created_at)); // newest first
                    if (latest3.size() > 3) latest3 = latest3.subList(0, 3);
                    latest3.sort((p1, p2) -> p1.created_at.compareTo(p2.created_at)); // oldest first for UI
                    
                    final java.util.List<forum.api.dto.PostDto> finalList = latest3;
                    Platform.runLater(() -> renderTopicPosts(finalList));
                }
            } catch (Exception e) {
                e.printStackTrace();
            }
        }).start();
    }

    private void renderTopicPosts(java.util.List<forum.api.dto.PostDto> posts) {
        if (topicPostsBox == null) return;
        topicPostsBox.getChildren().clear();
        User me = Session.currentUser();
        long myId = (me != null) ? me.getUserId() : -1;

        for (var p : posts) {
            if (p.author_id == myId) {
                // Own message (green, right-aligned)
                HBox row = new HBox();
                row.setAlignment(Pos.TOP_RIGHT);
                Label content = new Label(p.content);
                content.setStyle("-fx-background-color: #bbf7d0; -fx-padding: 10 16; -fx-background-radius: 16 16 0 16; -fx-text-fill: #166534; -fx-font-size: 13px;");
                content.setWrapText(true);
                row.getChildren().add(content);
                topicPostsBox.getChildren().add(row);
            } else {
                // Other message (gray, left-aligned)
                HBox row = new HBox(8);
                row.setAlignment(Pos.TOP_LEFT);
                String init = initial(p.author);
                Label avatar = new Label(init);
                avatar.setStyle("-fx-background-color: #8b5cf6; -fx-text-fill: white; -fx-min-width: 28; -fx-min-height: 28; -fx-alignment: center; -fx-background-radius: 14; -fx-font-weight: bold; -fx-font-size: 12px;");
                
                VBox textCol = new VBox(3);
                Label name = new Label(p.author);
                name.setStyle("-fx-text-fill: #8b5cf6; -fx-font-weight: bold; -fx-font-size: 11px;");
                Label content = new Label(p.content);
                content.setStyle("-fx-background-color: #f1f5f9; -fx-padding: 10 16; -fx-background-radius: 0 16 16 16; -fx-text-fill: #334155; -fx-font-size: 13px;");
                content.setWrapText(true);
                textCol.getChildren().addAll(name, content);
                row.getChildren().addAll(avatar, textCol);
                topicPostsBox.getChildren().add(row);
            }
        }
        if (viewDiscussionLink != null) {
            topicPostsBox.getChildren().add(viewDiscussionLink);
        }
    }

    private void openTopic(StudentDashboardDto.TopicSummary topic) {
        Topic t = new Topic();
        t.setTopicId(topic.topicId);
        t.setTitle(topic.title);
        ViewState.setSelectedTopic(t);
        SceneManager.show("TopicDetail", "Smart Discussion Forum \u2014 " + topic.title);
    }

    @FXML
    private void onExportPdf() {
        if (exportPdfBtn.getUserData() instanceof Long topicId) {
            try {
                // The web app handles PDF export at this URL
                String url = "http://localhost:8000/topics/" + topicId + "/export-pdf";
                java.awt.Desktop.getDesktop().browse(new java.net.URI(url));
            } catch (Exception e) {
                e.printStackTrace();
            }
        }
    }

    // ───────────────────────────────────────────────────────────────
    //  Navigation handlers
    // ───────────────────────────────────────────────────────────────
    @FXML private void onDashboard()  { SceneManager.goStudentDashboard(); }
    @FXML private void onGroups()     { SceneManager.goGroups(); }
    @FXML private void onMyProgress() { SceneManager.goStudentAssessment(); }
    @FXML private void onNewTopic()   { SceneManager.goTopicCreation(); }
    @FXML private void onForum()      { SceneManager.goForumDashboard(); }
    @FXML private void onProfile()    { forum.app.SceneManager.goProfile(); }

    @FXML
    private void onTakeQuiz() {
        QuizDto q = ViewState.getSelectedQuiz();
        if (q == null) return;
        String token = Session.authToken();
        if (token == null) return;
        new Thread(() -> {
            try {
                var detail = api.getQuiz(token, q.quizId);
                ViewState.setSelectedQuizDetail(detail);
                Platform.runLater(SceneManager::goQuizFocusMode);
            } catch (Exception e) {
                e.printStackTrace();
            }
        }, "load-quiz").start();
    }

    @FXML private void onLogout() {
        String token = Session.authToken();
        Session.end();
        new Thread(() -> new AuthService().logout(token), "logout").start();
        SceneManager.show("Login", "ACES");
    }

    private String initial(String name) {
        if (name == null || name.isBlank()) return "?";
        return String.valueOf(name.trim().charAt(0)).toUpperCase();
    }
}