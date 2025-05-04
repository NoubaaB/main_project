
import { useDashboard } from "@/stores/Dashboard.js";
import { useNotification } from "@/stores/Notification";
import { useAuth } from "@/stores/Auth";
import { useProduct } from "@/stores/Product";
import { useUser } from "@/stores/User";
import { initSocket } from "@/echo";

export function establish() {
    let dashboard = useDashboard();
    let notification = useNotification();
    let auth = useAuth();
    let user = useUser();
    let product = useProduct();
    if (!auth.is_activate) {
        return 0;
    }
    initSocket();

    //start users private presence channel
    Echo.join(`users`)
        .here((users) => {
            user.connected_users = users
                .filter((u) => u.id != auth.getAuth.id)
        })
        .joining((_user) => {
            if (!user.connected_users.find((u) => u.id == _user.id) && _user.id != auth.getAuth.id) {
                user.join(_user);
            }
        })
        .leaving((_user) => {
            user.leave(_user);
        })
    //end users private presence channel
    //start articles websockets
    Echo.private(`articles`)
        .listen("CreateArticleEvent", (event => {
            console.log("CreateArticleEvent", event)
            if (dashboard.articles.find(article => article.id != event.article.id)) {
                dashboard.articles.push(event.article);
            }
        })).listen("UpdateArticleEvent", (event => {
            console.log("UpdateArticleEvent", event)
            let article = dashboard.articles.find(article => article.id == event.article.id);
            if (article) {
                article.date = event.article.date;
                article.description = event.article.description;
                article.price = event.article.price;
                article.is_private = event.article.is_private;
                article.star = event.article.star;
                article.users_id = event.article.users_id;
                article.user_id = event.article.user_id;
                article.product_id = event.article.product_id;
                article.bill_id = event.article.bill_id;
            }
        })).listen("DeleteArticleEvent", (event => {
            console.log("DeleteArticleEvent", event)
            dashboard.articles = dashboard.articles.filter(article => article.id != event.article.id);
        }));
    //end articles websockets

    //start bills websockets
    Echo.private(`bills`)
        .listen("CreateBillEvent", (event => {
            console.log("CreateBillEvent")
            if (!dashboard.bills.find(bill => bill.id == event.bill.id)) {
                dashboard.bills.push(event.bill);
            }
            event.articles.forEach(article_id => {
                let article = dashboard.articles.find(__article => __article.id == article_id);
                if (article) {
                    article.bill_id = event.bill.id;
                }
            })
        })).listen("UpdateBillEvent", (event => {
            let _bill = dashboard.bills.find(bill => bill.id == event.bill.id);
            console.log("UpdateBillEvent",event,_bill)
            if (_bill) {
                _bill.date = event.bill.date;
                _bill.amount = event.bill.amount;
                _bill.articles = event.bill.articles;
                _bill.articles.forEach(_article => {
                    let article = dashboard.articles.find(article => article.id == _article.id);
                    if (article) {
                        article.date = _article.date;
                        article.description = _article.description;
                        article.price = _article.price;
                        article.is_private = _article.is_private;
                        article.star = _article.star;
                        article.users_id = _article.users_id;
                        article.user_id = _article.user_id;
                        article.product_id = _article.product_id;
                        article.bill_id = _article.bill_id;
                    }
                });
                _bill.invoices = event.bill.invoices;
                _bill.user_id = event.bill.user_id;
            }
            _bill = dashboard.score_bills.find(bill => bill.id == event.bill.id);
            if (_bill) {
                _bill.date = event.bill.date;
                _bill.amount = event.bill.amount;
                _bill.articles = event.bill.articles;
                _bill.invoices = event.bill.invoices;
                _bill.user_id = event.bill.user_id;
            }
        })).listen("DeleteBillEvent", (event => {
            console.log("DeleteBillEvent", event);
            dashboard.bills = dashboard.bills.filter(bill => bill.id != event.bill.id);
            if (event.articles) {
                event.articles.forEach(article_id => {
                    let article = dashboard.articles.find(__article => __article.id == article_id);
                    if (article) {
                        article.bill_id = event.bill.id;
                    }
                })
            }
        }));
    //end bills websockets

    //start notification websockets
    Echo.private(`notifications.${auth.getAuth.id}`)
        .listen("NotificationEvent", (event => {
            console.log("NotificationEvent", event)
            notification.notifications.push(event.notification);
            ++notification.unread;
            notification.addToSnackBar(event.notification);
        }));
    //end notification websockets

    //start score-user websockets
    Echo.private(`user-score`)
        .listen("UserScoreEvent", (event => {
            console.log("UserScoreEvent", event)
            let _user = user.users.find(u => u.id == event.user.id);
            if (_user) _user.score = event.user.score;
        }));
    //end score-user websockets

    //start product websockets
    Echo.private(`products`)
        .listen("CreateProductEvent", (event => {
            console.log("CreateProductEvent", event)
            product.collect.push(event.product);
        }));
    //end product websockets
}