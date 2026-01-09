# SneakEazy Dataset

**Shoe Recommender System Training Dataset**

A realistic e-commerce dataset for training recommendation systems, featuring user interactions, product ratings, and engagement metrics.

---

## 📊 Dataset Overview

| Metric | Value |
|--------|-------|
| **Users** | 205 |
| **Products** | 557 |
| **Interactions** | 904 |
| **Brands** | 16 |
| **Categories** | 6 |
| **Average Rating** | 3.98 ⭐ |
| **Average Clicks** | 5.3 clicks |
| **Coverage** | 75.58% |
| **Sparsity** | 99.21% |

---

## 📁 Files

### **1. users.csv** (205 records)
User account information with realistic names.

**Columns**:
- `user_id`: Unique user identifier
- `name`: User's full name (e.g., "Jessica Walker")
- `email`: User's email address
- `created_at`: Account creation timestamp

**Sample**:
```csv
user_id,name,email,created_at
user_6960b56a491dd_1,Jessica Walker,jessica.walker957@example.com,2026-01-09 14:59:38
```

---

### **2. products.csv** (557 records)
Complete product catalog with features.

**Columns**:
- `product_id`: Unique product identifier
- `product_name`: Product name
- `brand`: Brand name (Nike, Adidas, New Balance, etc.)
- `category`: Product category (Sneakers, Running, Basketball, etc.)
- `original_price`: Original price (IDR)
- `sale_price`: Sale price if on discount
- `rating`: Average rating (1-5 stars)
- `rating_count`: Number of ratings

**Sample**:
```csv
product_id,product_name,brand,category,original_price,sale_price,rating,rating_count
1,Nike Air Max 90,Nike,Sneakers,Rp 1.799.000,,4.5,12
```

**Brand Distribution**:
- Nike: 168 products (30.2%)
- New Balance: 120 products (21.5%)
- Adidas: 86 products (15.4%)
- ON: 61 products (11.0%)
- Others: 122 products (21.9%)

---

### **3. interactions_ratings.csv** (904 records) ⭐ **Main Training Data**
User-product ratings for collaborative filtering.

**Columns**:
- `user_id`: User who rated
- `product_id`: Product being rated
- `rating`: Rating value (1-5 stars)
- `timestamp`: Unix timestamp

**Sample**:
```csv
user_id,product_id,rating,timestamp
user_6960b56a4ae97_21,52,4.0,1760173551
```

**Rating Distribution**:
- 5 stars: 40%
- 4 stars: 30%
- 3 stars: 15%
- 2 stars: 10%
- 1 star: 5%

---

### **4. interactions_all.csv** (904 records)
Complete interactions with engagement metrics.

**Columns**:
- `id`: Interaction ID
- `user_id`: User who interacted
- `product_id`: Product interacted with
- `rating`: Rating value (1-5 stars)
- `click_count`: Number of times user clicked/viewed product
- `view_score`: Engagement score (1-5, derived from clicks)
- `timestamp`: Unix timestamp

**Sample**:
```csv
id,user_id,product_id,rating,click_count,view_score,timestamp
1362,user_6960b56a4ae97_21,52,4.0,10,5.0,1760173551
```

**Click Patterns**:
- High ratings (4-5★): 3-10 clicks (high engagement)
- Medium ratings (3★): 2-5 clicks (moderate engagement)
- Low ratings (1-2★): 1-3 clicks (low engagement)

---

### **5. statistics.txt**
Comprehensive dataset statistics and analysis.

---

## 🎯 Use Cases

### **1. Collaborative Filtering**
```python
import pandas as pd
from surprise import Dataset, Reader, SVD

# Load ratings
ratings = pd.read_csv('interactions_ratings.csv')

# Train collaborative filtering model
reader = Reader(rating_scale=(1, 5))
data = Dataset.load_from_df(ratings[['user_id', 'product_id', 'rating']], reader)

svd = SVD()
svd.fit(data.build_full_trainset())
```

### **2. Content-Based Filtering**
```python
import pandas as pd
from sklearn.preprocessing import LabelEncoder
from sklearn.neighbors import NearestNeighbors

# Load products
products = pd.read_csv('products.csv')

# Feature engineering
products['brand_encoded'] = LabelEncoder().fit_transform(products['brand'])
products['category_encoded'] = LabelEncoder().fit_transform(products['category'])

# Train KNN
features = products[['brand_encoded', 'category_encoded', 'rating']]
knn = NearestNeighbors(n_neighbors=10)
knn.fit(features)
```

### **3. Hybrid Systems**
Combine collaborative and content-based approaches for better recommendations.

### **4. Engagement Analysis**
```python
# Analyze user engagement patterns
interactions = pd.read_csv('interactions_all.csv')

# Engagement by rating
engagement = interactions.groupby('rating')['click_count'].mean()
```

---

## 📊 Dataset Characteristics

### **Strengths** ✅
- **Realistic user names**: Real person names (not dummy_user_001)
- **Natural rating distribution**: Skewed towards positive ratings (realistic for e-commerce)
- **Engagement metrics**: Click counts and view scores for implicit feedback
- **Good coverage**: 75.58% of products have ratings
- **Diverse brands**: 16 different shoe brands
- **Multiple categories**: Sneakers, Running, Basketball, Lifestyle, Outdoor, Sandals

### **Realistic Patterns** 🎯
- **Brand preferences**: Users tend to prefer certain brands
- **Activity levels**: Power law distribution (few very active users, many casual users)
- **Engagement correlation**: Higher ratings correlate with more clicks
- **Temporal spread**: Interactions spread over 90 days

### **Sparsity** ⚠️
- Matrix sparsity: 99.21% (typical for recommender systems)
- This makes the dataset realistic and challenging for algorithms

---

## 🔬 Research Applications

This dataset is suitable for:

1. **Recommendation Algorithms**
   - User-based collaborative filtering
   - Item-based collaborative filtering
   - Matrix factorization (SVD, NMF)
   - Deep learning (Neural CF, AutoRec)

2. **Evaluation Metrics**
   - Precision@K
   - Recall@K
   - NDCG
   - Coverage
   - Diversity
   - Novelty

3. **Cold Start Problems**
   - New user recommendations
   - New product recommendations
   - Hybrid approaches

4. **Implicit Feedback**
   - Click-through rate prediction
   - Engagement modeling
   - View score optimization

---

## 📚 Citation

If you use this dataset in your research, please cite:

```
@dataset{sneakeazy2026,
  title={SneakEazy: Shoe Recommender System Dataset},
  author={SneakEazy Team},
  year={2026},
  publisher={GitHub},
  url={https://github.com/firdaz2581/dataset_sneakeazy}
}
```

---

## 📄 License

This dataset is released under the MIT License.

---

## 🤝 Contributing

Found an issue or want to improve the dataset? Please open an issue or pull request!

---

## 📞 Contact

For questions or collaborations, please open an issue on GitHub.

---

## 🚀 Quick Start

```bash
# Clone repository
git clone https://github.com/firdaz2581/dataset_sneakeazy.git
cd dataset_sneakeazy

# Load data
import pandas as pd

users = pd.read_csv('users.csv')
products = pd.read_csv('products.csv')
ratings = pd.read_csv('interactions_ratings.csv')
interactions = pd.read_csv('interactions_all.csv')

# Start building your recommender system!
```

---

**Last Updated**: 2026-01-09  
**Version**: 1.0  
**Status**: ✅ Production Ready
