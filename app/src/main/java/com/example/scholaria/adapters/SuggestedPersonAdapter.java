package com.example.scholaria.adapters;

import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.TextView;
import androidx.annotation.NonNull;
import androidx.recyclerview.widget.RecyclerView;
import com.example.scholaria.R;
import com.example.scholaria.models.SuggestedPerson;
import com.google.android.material.imageview.ShapeableImageView;
import java.util.List;

public class SuggestedPersonAdapter extends RecyclerView.Adapter<SuggestedPersonAdapter.ViewHolder> {
    private List<SuggestedPerson> suggestedPeople;

    public SuggestedPersonAdapter(List<SuggestedPerson> suggestedPeople) {
        this.suggestedPeople = suggestedPeople;
    }

    @NonNull
    @Override
    public ViewHolder onCreateViewHolder(@NonNull ViewGroup parent, int viewType) {
        View view = LayoutInflater.from(parent.getContext()).inflate(R.layout.item_suggested_person, parent, false);
        return new ViewHolder(view);
    }

    @Override
    public void onBindViewHolder(@NonNull ViewHolder holder, int position) {
        SuggestedPerson person = suggestedPeople.get(position);
        holder.tvSuggestedName.setText(person.getName());
        if (person.getImageResId() != 0) {
            holder.ivSuggestedProfile.setImageResource(person.getImageResId());
        }
    }

    @Override
    public int getItemCount() {
        return suggestedPeople.size();
    }

    public static class ViewHolder extends RecyclerView.ViewHolder {
        ShapeableImageView ivSuggestedProfile;
        TextView tvSuggestedName;

        public ViewHolder(@NonNull View itemView) {
            super(itemView);
            ivSuggestedProfile = itemView.findViewById(R.id.ivSuggestedProfile);
            tvSuggestedName = itemView.findViewById(R.id.tvSuggestedName);
        }
    }
}
