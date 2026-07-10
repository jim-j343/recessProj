package forum.config;

import java.io.File;

/** Central configuration for local storage and the (future) sync API. */
public final class DatabaseConfig {

    private DatabaseConfig() { }

    /** Per-user data directory: ~/.aces */
    public static final String APP_DIR =
            System.getProperty("user.home") + File.separator + ".aces";

    /** Local SQLite cache file. */
    public static final String DB_FILE = APP_DIR + File.separator + "aces-local.db";

    /** Base URL of the Laravel sync API (override with -Daces.api=...). */
    public static final String API_BASE_URL =
            System.getProperty("aces.api", "http://localhost:8000/api");

    public static String jdbcUrl() {
        return "jdbc:sqlite:" + DB_FILE;
    }

    public static void ensureAppDir() {
        File dir = new File(APP_DIR);
        if (!dir.exists()) dir.mkdirs();
    }
}
