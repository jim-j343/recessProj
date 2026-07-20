import re
from sklearn.feature_extraction.text import TfidfVectorizer
from sklearn.svm import LinearSVC
from sklearn.pipeline import Pipeline
from sklearn.model_selection import cross_val_score
import joblib
from training_data import TRAINING_DATA

# Separate text and labels
raw_texts = [item[0] for item in TRAINING_DATA]
labels = [item[1] for item in TRAINING_DATA]

def clean_text(text):
    """
    Strips punctuation, handles special characters like dashes, 
    and lowercases the text to ensure clean vocabulary tokens.
    """
    words = re.findall(r'\b\w+\b', text.lower())
    return " ".join(words)

# Preprocess all training texts
texts = [clean_text(t) for t in raw_texts]

# Build an optimized pipeline: TF-IDF vectorizer + Linear Support Vector Classifier
# - ngram_range=(1, 2) captures compound concepts as unified features[cite: 2]
# - sublinear_tf=True applies a logarithmic scale to word counts to diminish noise
classifier = Pipeline([
    ('tfidf', TfidfVectorizer(stop_words='english', ngram_range=(1, 2), sublinear_tf=True)),
    ('classifier', LinearSVC(C=1.0, random_state=42)),
])

# 5-fold cross-validation gives a reliable accuracy estimate[cite: 2]
scores = cross_val_score(classifier, texts, labels, cv=5)
print(f"Cross-validation accuracy: {scores.mean() * 100:.1f}% (+/- {scores.std() * 100:.1f}%)")
print(f"Fold scores: {[round(s * 100, 1) for s in scores]}")

# Train the FINAL model on all available data[cite: 2]
classifier.fit(texts, labels)

# SAVE the trained model to a file[cite: 2]
joblib.dump(classifier, 'topic_classifier.pkl')
print("Model saved to topic_classifier.pkl")