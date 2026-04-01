package com.example.scholaria.adapters;

import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.ImageView;
import android.widget.TextView;
import androidx.annotation.NonNull;
import androidx.recyclerview.widget.RecyclerView;
import com.example.scholaria.R;
import com.example.scholaria.models.Notification;
import java.util.List;

public class NotificationAdapter extends RecyclerView.Adapter<NotificationAdapter.ViewHolder> {
    private List<Notification> notifications;

    public NotificationAdapter(List<Notification> notifications) {
        this.notifications = notifications;
    }

    @NonNull
    @Override
    public ViewHolder onCreateViewHolder(@NonNull ViewGroup parent, int viewType) {
        View view = LayoutInflater.from(parent.getContext()).inflate(R.layout.item_notification_card, parent, false);
        return new ViewHolder(view);
    }

    @Override
    public void onBindViewHolder(@NonNull ViewHolder holder, int position) {
        Notification notif = notifications.get(position);
        holder.tvTitle.setText(notif.getTitle());
        holder.tvDesc.setText(notif.getDescription());
        holder.tvTime.setText(notif.getTime());

        holder.viewUnread.setVisibility(notif.isRead() ? View.GONE : View.VISIBLE);

        // Set icon based on type
        switch (notif.getType().toLowerCase()) {
            case "assignment":
                holder.ivIcon.setImageResource(android.R.drawable.ic_menu_edit);
                break;
            case "grade":
                holder.ivIcon.setImageResource(android.R.drawable.star_on);
                break;
            case "system":
            default:
                holder.ivIcon.setImageResource(android.R.drawable.ic_dialog_info);
                break;
        }
    }

    @Override
    public int getItemCount() {
        return notifications.size();
    }

    public static class ViewHolder extends RecyclerView.ViewHolder {
        ImageView ivIcon;
        TextView tvTitle, tvDesc, tvTime;
        View viewUnread;

        public ViewHolder(@NonNull View itemView) {
            super(itemView);
            ivIcon = itemView.findViewById(R.id.ivNotifIcon);
            tvTitle = itemView.findViewById(R.id.tvNotifTitle);
            tvDesc = itemView.findViewById(R.id.tvNotifDesc);
            tvTime = itemView.findViewById(R.id.tvNotifTime);
            viewUnread = itemView.findViewById(R.id.viewUnreadIndicator);
        }
    }
}
