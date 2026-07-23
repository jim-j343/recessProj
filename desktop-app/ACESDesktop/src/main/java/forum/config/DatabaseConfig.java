package forum.config;

import java.io.File;

/** Central configuration for local storage and the (future) sync API. */
public final class DatabaseConfig {

    private DatabaseConfig() { }

    /** Per-user data directory: ~/.aces */
    public static final String APP_DIR =
            System.getProperty("user.home") + File.separator + ".aces";

    /** Base URL of the Laravel sync API (override with -Daces.api=...). */
    public static final String API_BASE_URL =
            System.getProperty("aces.api", "http://localhost:8000/api");

    /** Local SQLite cache file (override with -Daces.db=... — used by tests
     *  to point at an isolated temp file instead of the real user cache). */
    public static String dbFile() {
        return System.getProperty("aces.db", APP_DIR + File.separator + "aces-local.db");
    }

    public static String jdbcUrl() {
        return "jdbc:sqlite:" + dbFile();
    }

    public static void ensureAppDir() {
        File dir = new File(dbFile()).getParentFile();
        if (dir != null && !dir.exists()) dir.mkdirs();
    }
}