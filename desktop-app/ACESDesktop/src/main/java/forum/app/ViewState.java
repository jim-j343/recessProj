package forum.app;

import forum.models.Topic;

/** Lightweight holder for data passed between screens (e.g. the selected topic). */
public final class ViewState {
    private static Topic selectedTopic;
    private ViewState() { }
    public static Topic getSelectedTopic()          { return selectedTopic; }
    public static void setSelectedTopic(Topic t)     { selectedTopic = t; }
}
