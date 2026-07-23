package forum.api;

/**
 * Thrown when the server is reachable but rejects the request
 * (e.g. 401 invalid credentials, 422 validation error, 403 blacklisted).
 * Distinct from IOException, which signals the server is unreachable (offline).
 */
public class ApiException extends Exception {

    private final int status;

    public ApiException(int status, String message) {
        super(message);
        this.status = status;
    }

    public int status() {
        return status;
    }
}
