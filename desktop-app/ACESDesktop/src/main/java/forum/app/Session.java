package forum.app;

import forum.models.User;

/** Holds the currently authenticated user and (later) the API auth token. */
public final class Session {

    private static User currentUser;
    private static String authToken;

    private Session() { }

    public static void begin(User user, String token) {
        currentUser = user;
        authToken = token;
    }

    public static User currentUser() { return currentUser; }
    public static String authToken()  { return authToken; }
    public static boolean isAuthenticated() { return currentUser != null; }

    public static void end() {
        currentUser = null;
        authToken = null;
    }
}
