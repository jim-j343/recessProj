package forum.api;
import forum.api.dto.GroupDto;
import forum.api.dto.AdminAnalyticsDto;
import forum.api.dto.AdminDashboardDto;
import forum.api.dto.AdminMemberDto;
import forum.api.dto.NotificationDto;
import forum.api.dto.QuizDto;
import forum.api.dto.QuizDetailResponse;
import forum.api.dto.QuizResultDto;
import forum.api.dto.StudentDashboardDto;
import forum.api.dto.StudentProgressDto;
import forum.api.dto.AdminRemovalsResponseDto;
import forum.api.dto.AdminReportsResponseDto;
import forum.api.dto.LecturerDashboardDto;
import com.fasterxml.jackson.core.type.TypeReference;
import com.fasterxml.jackson.databind.DeserializationFeature;
import com.fasterxml.jackson.databind.JsonNode;
import com.fasterxml.jackson.databind.ObjectMapper;

import forum.api.dto.AuthResponse;
import forum.api.dto.PostDto;
import forum.api.dto.TopicDetailResponse;
import forum.api.dto.TopicDto;
import forum.config.DatabaseConfig;

import java.io.IOException;
import java.net.URI;
import java.net.http.HttpClient;
import java.net.http.HttpRequest;
import java.net.http.HttpResponse;
import java.time.Duration;
import java.util.LinkedHashMap;
import java.util.List;
import java.util.Map;

/**
 * Thin HTTP client for the Laravel token API (java.net.http + Jackson).
 *
 * Base URL comes from {@link DatabaseConfig#API_BASE_URL}
 * (default http://localhost:8000/api, override with -Daces.api=...).
 *
 * Success  → returns a parsed DTO.
 * Rejected → throws {@link ApiException} (server reachable, non-2xx).
 * Offline  → throws IOException / InterruptedException (server unreachable).
 */
public class ApiClient {

    private final HttpClient http = HttpClient.newBuilder()
            .connectTimeout(Duration.ofSeconds(60))
            .build();

    private final ObjectMapper mapper = new ObjectMapper()
            .configure(DeserializationFeature.FAIL_ON_UNKNOWN_PROPERTIES, false);

    private final String base = DatabaseConfig.API_BASE_URL;

    /** POST /login — email + password → { token, user }. */
    public AuthResponse login(String email, String password)
            throws ApiException, IOException, InterruptedException {
        Map<String, Object> body = new LinkedHashMap<>();
        body.put("email", email);
        body.put("password", password);
        body.put("device_name", "ACES Desktop");
        return postForAuth("/login", body);
    }

    /** POST /register — creates the account and returns { token, user }. */
    public AuthResponse register(String username, String email, String password, String role)
            throws ApiException, IOException, InterruptedException {
        Map<String, Object> body = new LinkedHashMap<>();
        body.put("username", username);
        body.put("email", email);
        body.put("role", role);
        body.put("password", password);
        body.put("password_confirmation", password);
        body.put("rules_accepted", true);
        body.put("device_name", "ACES Desktop");
        return postForAuth("/register", body);
    }

    /** Best-effort token revocation; ignores failures (e.g. offline). */
    public void logout(String token) {
        if (token == null || token.isBlank()) return;
        try {
            HttpRequest req = HttpRequest.newBuilder(URI.create(base + "/logout"))
                    .timeout(Duration.ofSeconds(60))
                    .header("Accept", "application/json")
                    .header("Authorization", "Bearer " + token)
                    .POST(HttpRequest.BodyPublishers.noBody())
                    .build();
            http.send(req, HttpResponse.BodyHandlers.ofString());
        } catch (Exception ignored) {
            // logging out is best-effort; the local session is cleared regardless
        }
    }

    // ---------------------------------------------------------------
    //  Notifications
    // ---------------------------------------------------------------

    /** GET /api/notifications — unread count + latest unread messages. */
    public NotificationDto fetchNotifications(String token)
            throws ApiException, IOException, InterruptedException {
        HttpResponse<String> resp = send(request("/notifications", token).GET().build());
        ok(resp);
        return mapper.readValue(resp.body(), NotificationDto.class);
    }

    /** GET /api/notifications/all — unread count + ALL notifications. */
    public NotificationDto fetchAllNotifications(String token)
            throws ApiException, IOException, InterruptedException {
        HttpResponse<String> resp = send(request("/notifications/all", token).GET().build());
        ok(resp);
        return mapper.readValue(resp.body(), NotificationDto.class);
    }

    /** POST /api/notifications/read-all — mark every notification as read. */
    public void markAllNotificationsRead(String token)
            throws ApiException, IOException, InterruptedException {
        HttpResponse<String> resp = send(request("/notifications/read-all", token)
                .POST(HttpRequest.BodyPublishers.noBody()).build());
        ok(resp);
    }

    /** POST /api/notifications/{id}/read — mark a single notification as read. */
    public void markNotificationRead(String token, long notificationId)
            throws ApiException, IOException, InterruptedException {
        HttpResponse<String> resp = send(request("/notifications/" + notificationId + "/read", token)
                .POST(HttpRequest.BodyPublishers.noBody()).build());
        ok(resp);
    }

    // ---------------------------------------------------------------
    //  Forum (token-protected)
    // ---------------------------------------------------------------

    /** GET /topics — topics from the caller's groups. */
    public List<TopicDto> listTopics(String token)
            throws ApiException, IOException, InterruptedException {
        HttpResponse<String> resp = send(request("/topics", token).GET().build());
        ok(resp);
        return mapper.readValue(resp.body(), new TypeReference<List<TopicDto>>() { });
    }

    /** POST /topics — create a topic (its content becomes the first post). */
    public TopicDto createTopic(String token, long groupId, String title,
                                String category, String content)
            throws ApiException, IOException, InterruptedException {
        Map<String, Object> body = new LinkedHashMap<>();
        body.put("group_id", groupId);
        body.put("title", title);
        body.put("category", category);
        body.put("content", content);
        HttpResponse<String> resp = send(request("/topics", token)
                .POST(HttpRequest.BodyPublishers.ofString(json(body))).build());
        ok(resp);
        return mapper.readValue(resp.body(), TopicDto.class);
    }

    /** GET /topics/{id} — the topic plus its posts. */
    public TopicDetailResponse getTopic(String token, long topicId)
            throws ApiException, IOException, InterruptedException {
        HttpResponse<String> resp = send(request("/topics/" + topicId, token).GET().build());
        ok(resp);
        return mapper.readValue(resp.body(), TopicDetailResponse.class);
    }

    /** POST /topics/{id}/posts — add a reply. */
    public PostDto createPost(String token, long topicId, String content, Long parentPostId)
            throws ApiException, IOException, InterruptedException {
        return createPost(token, topicId, content, parentPostId, List.of());
    }

    /** POST /topics/{id}/posts, optionally hiding the reply from selected students. */
    public PostDto createPost(String token, long topicId, String content, Long parentPostId,
                              List<Long> excludedUserIds)
            throws ApiException, IOException, InterruptedException {
        Map<String, Object> body = new LinkedHashMap<>();
        body.put("content", content);
        if (parentPostId != null) body.put("parent_post_id", parentPostId);
        if (excludedUserIds != null && !excludedUserIds.isEmpty()) {
            body.put("excluded_users", excludedUserIds);
        }
        HttpResponse<String> resp = send(request("/topics/" + topicId + "/posts", token)
                .POST(HttpRequest.BodyPublishers.ofString(json(body))).build());
        ok(resp);
        return mapper.readValue(resp.body(), PostDto.class);
    }

    /** POST /posts/{id}/report */
    public void reportPost(String token, long postId, String reason)
            throws ApiException, IOException, InterruptedException {
        Map<String, Object> body = new LinkedHashMap<>();
        body.put("reason", reason);
        HttpResponse<String> resp = send(request("/posts/" + postId + "/report", token)
                .POST(HttpRequest.BodyPublishers.ofString(json(body))).build());
        ok(resp);
    }

    /** PUT /posts/{id} */
    public PostDto updatePost(String token, long postId, String content)
            throws ApiException, IOException, InterruptedException {
        Map<String, Object> body = new LinkedHashMap<>();
        body.put("content", content);
        HttpResponse<String> resp = send(request("/posts/" + postId, token)
                .method("PUT", HttpRequest.BodyPublishers.ofString(json(body))).build());
        ok(resp);
        return mapper.readValue(resp.body(), PostDto.class);
    }

    /** DELETE /posts/{id} */
    public void deletePost(String token, long postId)
            throws ApiException, IOException, InterruptedException {
        HttpResponse<String> resp = send(request("/posts/" + postId, token)
                .method("DELETE", HttpRequest.BodyPublishers.noBody()).build());
        ok(resp);
    }

// ---------------------------------------------------------------
//  Groups
// ---------------------------------------------------------------

/** GET /api/groups */
public List<GroupDto> listGroups(String token)
        throws ApiException, IOException, InterruptedException {
    HttpResponse<String> resp = send(request("/groups", token).GET().build());
    ok(resp);
    return mapper.readValue(resp.body(), new TypeReference<List<GroupDto>>() {});
}

/** POST /api/groups */
public GroupDto createGroup(String token, String name, String description)
        throws ApiException, IOException, InterruptedException {
    Map<String, Object> body = new LinkedHashMap<>();
    body.put("name", name);
    body.put("description", description);
    HttpResponse<String> resp = send(request("/groups", token)
            .POST(HttpRequest.BodyPublishers.ofString(json(body))).build());
    ok(resp);
    return mapper.readValue(resp.body(), GroupDto.class);
}

/** POST /api/groups/{id}/join */
public void joinGroup(String token, long groupId)
        throws ApiException, IOException, InterruptedException {
    HttpResponse<String> resp = send(request("/groups/" + groupId + "/join", token)
            .POST(HttpRequest.BodyPublishers.noBody()).build());
    ok(resp);
}

/** GET /api/groups/{id}/members */
public com.fasterxml.jackson.databind.JsonNode groupMembers(String token, long groupId)
        throws ApiException, IOException, InterruptedException {
    HttpResponse<String> resp = send(request("/groups/" + groupId + "/members", token).GET().build());
    ok(resp);
    return mapper.readTree(resp.body());
}

/** PATCH /api/groups/{groupId}/members/{userId}/approve */
public void approveMember(String token, long groupId, long userId)
        throws ApiException, IOException, InterruptedException {
    HttpResponse<String> resp = send(request(
            "/groups/" + groupId + "/members/" + userId + "/approve", token)
            .method("PATCH", HttpRequest.BodyPublishers.noBody()).build());
    ok(resp);
}

/** PUT /api/groups/{id} */
public GroupDto updateGroup(String token, long groupId, String name, String courseName, String description, int warningDays, int blacklistDays)
        throws ApiException, IOException, InterruptedException {
    Map<String, Object> body = new LinkedHashMap<>();
    body.put("name", name);
    body.put("course_name", courseName);
    body.put("description", description);
    body.put("inactivity_warning_days", warningDays);
    body.put("blacklist_duration_days", blacklistDays);
    HttpResponse<String> resp = send(request("/groups/" + groupId, token)
            .method("PUT", HttpRequest.BodyPublishers.ofString(json(body))).build());
    ok(resp);
    return mapper.readValue(resp.body(), GroupDto.class);
}

/** DELETE /api/groups/{id} */
public void deleteGroup(String token, long groupId)
        throws ApiException, IOException, InterruptedException {
    HttpResponse<String> resp = send(request("/groups/" + groupId, token)
            .DELETE().build());
    ok(resp);
}

/** POST /api/groups/{id}/add-member */
public void addMemberGroup(String token, long groupId, String username)
        throws ApiException, IOException, InterruptedException {
    Map<String, Object> body = new LinkedHashMap<>();
    body.put("username", username);
    HttpResponse<String> resp = send(request("/groups/" + groupId + "/add-member", token)
            .POST(HttpRequest.BodyPublishers.ofString(json(body))).build());
    ok(resp);
}

/** DELETE /api/groups/{id}/members/{userId} */
public void removeMemberGroup(String token, long groupId, long userId, String reason)
        throws ApiException, IOException, InterruptedException {
    Map<String, Object> body = new LinkedHashMap<>();
    body.put("reason", reason);
    
    // HTTP DELETE with body isn't fully supported by all servers/clients, 
    // but Java 11 HttpClient supports custom methods. We use method("DELETE", body).
    HttpResponse<String> resp = send(request("/groups/" + groupId + "/members/" + userId, token)
            .method("DELETE", HttpRequest.BodyPublishers.ofString(json(body))).build());
    ok(resp);
}

// ---------------------------------------------------------------
//  Lecturer
// ---------------------------------------------------------------

    public LecturerDashboardDto getLecturerDashboard(String token) throws ApiException, IOException, InterruptedException {
        HttpResponse<String> resp = send(request("/lecturer/dashboard", token).GET().build());
        ok(resp);
        return mapper.readValue(resp.body(), LecturerDashboardDto.class);
    }

// ---------------------------------------------------------------
//  Quizzes
// ---------------------------------------------------------------

/** GET /api/quizzes — student: available quizzes */
public List<QuizDto> listQuizzes(String token)
        throws ApiException, IOException, InterruptedException {
    HttpResponse<String> resp = send(request("/quizzes", token).GET().build());
    ok(resp);
    return mapper.readValue(resp.body(), new TypeReference<List<QuizDto>>() {});
}

/** GET /api/quizzes/my — lecturer: their own quizzes */
public List<QuizDto> myQuizzes(String token)
        throws ApiException, IOException, InterruptedException {
    HttpResponse<String> resp = send(request("/quizzes/my", token).GET().build());
    ok(resp);
    return mapper.readValue(resp.body(), new TypeReference<List<QuizDto>>() {});
}

/** GET /api/quizzes/{id} — quiz with questions */
public QuizDetailResponse getQuiz(String token, long quizId)
        throws ApiException, IOException, InterruptedException {
    HttpResponse<String> resp = send(request("/quizzes/" + quizId, token).GET().build());
    ok(resp);
    return mapper.readValue(resp.body(), QuizDetailResponse.class);
}

/** POST /api/quizzes/{id}/submit */
public void submitQuiz(String token, long quizId, Map<Long, Long> answers, boolean autoSubmit)
        throws ApiException, IOException, InterruptedException {
    Map<String, Object> body = new LinkedHashMap<>();
    Map<String, Object> answerMap = new LinkedHashMap<>();
    answers.forEach((qId, aId) -> answerMap.put(String.valueOf(qId), aId));
    body.put("answers", answerMap);
    if (autoSubmit) body.put("auto_submit", true);
    HttpResponse<String> resp = send(request("/quizzes/" + quizId + "/submit", token)
            .POST(HttpRequest.BodyPublishers.ofString(json(body))).build());
    ok(resp);
}

/** GET /api/quizzes/{id}/results — student: their own result */
public QuizResultDto myQuizResult(String token, long quizId)
        throws ApiException, IOException, InterruptedException {
    HttpResponse<String> resp = send(request("/quizzes/" + quizId + "/results", token).GET().build());
    ok(resp);
    return mapper.readValue(resp.body(), QuizResultDto.class);
}

/** GET /api/quizzes/{id}/all-results — lecturer: all student results */
public List<QuizResultDto> allQuizResults(String token, long quizId)
        throws ApiException, IOException, InterruptedException {
    HttpResponse<String> resp = send(request("/quizzes/" + quizId + "/all-results", token).GET().build());
    ok(resp);
    return mapper.readValue(resp.body(), new TypeReference<List<QuizResultDto>>() {});
}

public StudentDashboardDto studentDashboard(String token)
        throws ApiException, IOException, InterruptedException {
    HttpResponse<String> resp = send(request("/student/dashboard", token).GET().build());
    ok(resp);
    return mapper.readValue(resp.body(), StudentDashboardDto.class);
}

public StudentProgressDto studentProgress(String token)
        throws ApiException, IOException, InterruptedException {
    HttpResponse<String> resp = send(request("/student/progress", token).GET().build());
    ok(resp);
    return mapper.readValue(resp.body(), StudentProgressDto.class);
}

    // ---------------------------------------------------------------
    //  Admin
    // ---------------------------------------------------------------

    public AdminDashboardDto adminDashboard(String token)
            throws ApiException, IOException, InterruptedException {
        HttpResponse<String> resp = send(request("/admin/dashboard", token).GET().build());
        ok(resp);
        return mapper.readValue(resp.body(), AdminDashboardDto.class);
    }

    public AdminAnalyticsDto adminAnalytics(String token)
            throws ApiException, IOException, InterruptedException {
        HttpResponse<String> resp = send(request("/admin/analytics", token).GET().build());
        ok(resp);
        return mapper.readValue(resp.body(), AdminAnalyticsDto.class);
    }

    public List<AdminMemberDto> adminMembers(String token, String filter, String search)
            throws ApiException, IOException, InterruptedException {
        String path = "/admin/members?filter=" + encode(filter == null ? "all" : filter);
        if (search != null && !search.isBlank()) path += "&search=" + encode(search.trim());
        HttpResponse<String> resp = send(request(path, token).GET().build());
        ok(resp);
        JsonNode root = mapper.readTree(resp.body());
        return mapper.readValue(root.get("members").get("data").traverse(), new TypeReference<List<AdminMemberDto>>() {});
    }

    public AdminMemberDto blacklistMember(String token, long userId, String reason, int days)
            throws ApiException, IOException, InterruptedException {
        Map<String, Object> body = new LinkedHashMap<>();
        body.put("reason", reason);
        body.put("days", days);
        HttpResponse<String> resp = send(request("/admin/blacklist/" + userId, token)
                .POST(HttpRequest.BodyPublishers.ofString(json(body))).build());
        ok(resp);
        return mapper.treeToValue(mapper.readTree(resp.body()).get("member"), AdminMemberDto.class);
    }

    public AdminMemberDto liftBlacklist(String token, long userId)
            throws ApiException, IOException, InterruptedException {
        HttpResponse<String> resp = send(request("/admin/lift-blacklist/" + userId, token)
                .POST(HttpRequest.BodyPublishers.noBody()).build());
        ok(resp);
        return mapper.treeToValue(mapper.readTree(resp.body()).get("member"), AdminMemberDto.class);
    }

    public AdminRemovalsResponseDto getRemovals(String token, String filter)
            throws ApiException, IOException, InterruptedException {
        String path = "/admin/removals";
        if (filter != null && !filter.isBlank()) path += "?filter=" + encode(filter);
        HttpResponse<String> resp = send(request(path, token).GET().build());
        ok(resp);
        return mapper.readValue(resp.body(), AdminRemovalsResponseDto.class);
    }

    public void markRemovalReviewed(String token, long removalId)
            throws ApiException, IOException, InterruptedException {
        HttpResponse<String> resp = send(request("/admin/removals/" + removalId + "/review", token)
                .POST(HttpRequest.BodyPublishers.noBody()).build());
        ok(resp);
    }

    // ---------------------------------------------------------------
    //  Low-level helpers
    // ---------------------------------------------------------------

    private HttpRequest.Builder request(String path, String token) {
        HttpRequest.Builder b = HttpRequest.newBuilder(URI.create(base + path))
                .timeout(Duration.ofSeconds(60))
                .header("Accept", "application/json")
                .header("Content-Type", "application/json");
        if (token != null && !token.isBlank()) b.header("Authorization", "Bearer " + token);
        return b;
    }

    private HttpResponse<String> send(HttpRequest req) throws IOException, InterruptedException {
        return http.send(req, HttpResponse.BodyHandlers.ofString());
    }

    private String json(Map<String, Object> body) throws IOException {
        return mapper.writeValueAsString(body);
    }

    private String encode(String value) {
        return java.net.URLEncoder.encode(value, java.nio.charset.StandardCharsets.UTF_8);
    }

    private void ok(HttpResponse<String> resp) throws ApiException {
        int sc = resp.statusCode();
        if (sc < 200 || sc >= 300) throw new ApiException(sc, extractMessage(resp.body(), sc));
    }

    private AuthResponse postForAuth(String path, Map<String, Object> body)
            throws ApiException, IOException, InterruptedException {
        String json = mapper.writeValueAsString(body);
        HttpRequest req = HttpRequest.newBuilder(URI.create(base + path))
                .timeout(Duration.ofSeconds(60))
                .header("Content-Type", "application/json")
                .header("Accept", "application/json")
                .POST(HttpRequest.BodyPublishers.ofString(json))
                .build();

        HttpResponse<String> resp = http.send(req, HttpResponse.BodyHandlers.ofString());
        int sc = resp.statusCode();
        if (sc >= 200 && sc < 300) {
            return mapper.readValue(resp.body(), AuthResponse.class);
        }
        throw new ApiException(sc, extractMessage(resp.body(), sc));
    }

    /** Pulls a human-readable message out of a Laravel error body. */
    private String extractMessage(String body, int sc) {
        try {
            JsonNode root = mapper.readTree(body);
            // Validation errors: { "message": "...", "errors": { "field": ["..."] } }
            if (root.has("errors") && root.get("errors").isObject()) {
                var fields = root.get("errors").elements();
                if (fields.hasNext()) {
                    JsonNode first = fields.next();
                    if (first.isArray() && first.size() > 0) return first.get(0).asText();
                }
            }
            if (root.has("message")) return root.get("message").asText();
        } catch (Exception ignored) {
            // fall through to a generic message
        }
        return "Request failed (HTTP " + sc + ").";
    }

    // ── Profile ───────────────────────────────────────────────────────────────

    /**
     * PATCH /api/profile — update the signed-in user's username.
     */
    public void updateProfile(String token, String newUsername)
            throws ApiException, IOException, InterruptedException {
        Map<String, Object> body = new LinkedHashMap<>();
        body.put("username", newUsername);

        HttpRequest req = HttpRequest.newBuilder()
                .uri(URI.create(base + "/profile"))
                .header("Content-Type", "application/json")
                .header("Accept", "application/json")
                .header("Authorization", "Bearer " + token)
                .method("PATCH", HttpRequest.BodyPublishers.ofString(mapper.writeValueAsString(body)))
                .build();

        HttpResponse<String> resp = http.send(req, HttpResponse.BodyHandlers.ofString());
        int sc = resp.statusCode();
        if (sc < 200 || sc >= 300) throw new ApiException(sc, extractMessage(resp.body(), sc));
    }

    /**
     * PATCH /api/profile/password — change the signed-in user's password.
     */
    public void updatePassword(String token, String currentPassword,
                               String newPassword, String confirmPassword)
            throws ApiException, IOException, InterruptedException {
        Map<String, Object> body = new LinkedHashMap<>();
        body.put("current_password", currentPassword);
        body.put("password", newPassword);
        body.put("password_confirmation", confirmPassword);

        HttpRequest req = HttpRequest.newBuilder()
                .uri(URI.create(base + "/profile/password"))
                .header("Content-Type", "application/json")
                .header("Accept", "application/json")
                .header("Authorization", "Bearer " + token)
                .method("PATCH", HttpRequest.BodyPublishers.ofString(mapper.writeValueAsString(body)))
                .build();

        HttpResponse<String> resp = http.send(req, HttpResponse.BodyHandlers.ofString());
        int sc = resp.statusCode();
        if (sc < 200 || sc >= 300) throw new ApiException(sc, extractMessage(resp.body(), sc));
    }

    // ── Moderation: Post Reports ─────────────────────────────────────
    
    public AdminReportsResponseDto getReports(String token, String filter) throws IOException, InterruptedException, ApiException {
        String url = base + "/admin/reports?filter=" + encode(filter);
        HttpRequest request = HttpRequest.newBuilder()
                .uri(URI.create(url))
                .header("Authorization", "Bearer " + token)
                .header("Accept", "application/json")
                .GET()
                .build();
        HttpResponse<String> response = http.send(request, HttpResponse.BodyHandlers.ofString());
        ok(response);
        return mapper.readValue(response.body(), AdminReportsResponseDto.class);
    }
    
    public void markReportReviewed(String token, long reportId) throws IOException, InterruptedException, ApiException {
        String url = base + "/admin/reports/" + reportId + "/review";
        HttpRequest request = HttpRequest.newBuilder()
                .uri(URI.create(url))
                .header("Authorization", "Bearer " + token)
                .header("Accept", "application/json")
                .POST(HttpRequest.BodyPublishers.noBody())
                .build();
        HttpResponse<String> response = http.send(request, HttpResponse.BodyHandlers.ofString());
        ok(response);
    }
}
