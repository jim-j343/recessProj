package forum.app;

/**
 * Implemented by any controller that fetches live data and needs
 * to be re-populated every time its cached scene is shown again.
 *
 * SceneManager.show() calls refresh() automatically on every
 * cached-screen navigation so users always see current data.
 */
public interface Refreshable {
    void refresh();
}
