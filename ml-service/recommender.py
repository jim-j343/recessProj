import pandas as pd
import numpy as np
from sklearn.metrics.pairwise import cosine_similarity

# Engagement weights per action type
WEIGHTS = {'view': 1, 'reply': 2, 'post': 3, 'export': 2}


def build_user_topic_matrix(engagements):
    """
    engagements: list of dicts with user_id, topic_id, engagement_type
    Returns: a DataFrame (matrix) where rows=users, cols=topics, values=score
    """
    rows = []
    for e in engagements:
        rows.append({
            'user_id': e['user_id'],
            'topic_id': e['topic_id'],
            'score': WEIGHTS.get(e['engagement_type'], 1)
        })

    df = pd.DataFrame(rows)
    # Group by user+topic, sum scores (user may engage multiple times)
    df = df.groupby(['user_id', 'topic_id'])['score'].sum().reset_index()
    # Pivot to matrix: rows=users, columns=topics
    matrix = df.pivot(index='user_id', columns='topic_id', values='score').fillna(0)
    return matrix


def get_recommendations(target_user_id, engagements, top_n=5):
    """
    Returns top_n topic IDs recommended for target_user_id
    """
    matrix = build_user_topic_matrix(engagements)

    # If user not in matrix, return empty list
    if target_user_id not in matrix.index:
        return []

    # Calculate cosine similarity between all users
    similarity_matrix = cosine_similarity(matrix)
    similarity_df = pd.DataFrame(
        similarity_matrix,
        index=matrix.index,
        columns=matrix.index
    )

    # Get similarity scores for target user, sorted descending
    user_similarities = similarity_df[target_user_id].sort_values(ascending=False)
    # Remove the user themselves
    user_similarities = user_similarities.drop(target_user_id)

    # Topics the target user has already seen
    already_seen = set(matrix.loc[target_user_id][matrix.loc[target_user_id] > 0].index)

    # Build weighted recommendation scores
    recommendation_scores = {}
    for other_user, similarity in user_similarities.items():
        if similarity <= 0:
            continue
        # Topics this similar user engaged with
        other_topics = matrix.loc[other_user]
        for topic_id, score in other_topics.items():
            if score > 0 and topic_id not in already_seen:
                if topic_id not in recommendation_scores:
                    recommendation_scores[topic_id] = 0
                # Weight by both the engagement score and user similarity
                recommendation_scores[topic_id] += similarity * score

    # Sort by score and return top N topic IDs
    sorted_recs = sorted(
        recommendation_scores.items(),
        key=lambda x: x[1],
        reverse=True
    )
    return [(topic_id, round(score, 4)) for topic_id, score in sorted_recs[:top_n]]


if __name__ == '__main__':
    # Simulated engagement data (normally fetched from MySQL/Supabase)
    sample_engagements = [
        {'user_id': 1, 'topic_id': 10, 'engagement_type': 'view'},
        {'user_id': 1, 'topic_id': 11, 'engagement_type': 'post'},
        {'user_id': 1, 'topic_id': 12, 'engagement_type': 'reply'},
        {'user_id': 2, 'topic_id': 10, 'engagement_type': 'view'},
        {'user_id': 2, 'topic_id': 11, 'engagement_type': 'view'},
        {'user_id': 2, 'topic_id': 13, 'engagement_type': 'post'},
        {'user_id': 2, 'topic_id': 14, 'engagement_type': 'reply'},
        {'user_id': 3, 'topic_id': 12, 'engagement_type': 'view'},
        {'user_id': 3, 'topic_id': 13, 'engagement_type': 'post'},
        {'user_id': 3, 'topic_id': 15, 'engagement_type': 'view'},
    ]

    recs = get_recommendations(target_user_id=1, engagements=sample_engagements)
    print("Recommendations for user 1:")
    for topic_id, score in recs:
        print(f"  Topic {topic_id} — confidence score: {score}")