#test_classifier.py
import joblib

# Load the saved model
classifier = joblib.load('topic_classifier.pkl')

# Test with new topics the model has never seen — all genuinely belong
# to one of the 12 trained categories, spanning a good spread of them
test_topics = [
    "how do you handle merge conflicts in git",
    "what is the difference between a primary key and a foreign key",
    "explain the waterfall model of software development",
    "how does the newton raphson method actually work",
    "what makes a good user story for a sprint",
    "difference between tcp and udp protocols",
]

for topic in test_topics:
    predicted_category = classifier.predict([topic])[0]
    print(f"Topic: '{topic}'")
    print(f"Predicted: {predicted_category}")
    print()


# Run it:
#python test_classifier.py