<template>
  <div>
    <button type="button" class="btn m-0 p-1 shadow-none">
      <i
        class="fas fa-heart mr-1"
        :class="{
          'red-text': this.isLikedBy,
          'animated heartBeat fast': this.gotToLike,
        }"
        @click="clickLike"
      />
      <!-- @clickハートアイコンがクリックされた時にclickLikeメソッドを実行 -->
    </button>
    {{ countLikes }}
    <!-- マスタッシュ構文で表示 -->
  </div>
</template>

<script>
export default {
  props: {
    initialIsLikedBy: {
      type: Boolean,
      default: false,
    },
    initialCountLikes: {
      type: Number,
      default: 0,
    },
    authorized: {
      type: Boolean,
      default: false,
    },
    endpoint: {
      type: String,
    },
  },

  data() {
    return {
      isLikedBy: this.initialIsLikedBy,
      countLikes: this.initialCountLikes,
      // bladeから渡されたいいね数が入ったプロパティinitialCountLikesを、countLikesにセット
      gotToLike: false,
    };
  },

  methods: {
    clickLike() {
      if (!this.authorized) {
        alert("いいね機能はログイン中のみ使用できます");
        return;
      }

      this.isLikedBy ? this.unlike() : this.like();
    },
    async like() {
      const response = await axios.put(this.endpoint);

      this.isLikedBy = true;
      this.countLikes = response.data.countLikes;
      this.gotToLike = true;
    },
    async unlike() {
      const response = await axios.delete(this.endpoint);

      this.isLikedBy = false;
      this.countLikes = response.data.countLikes;
      this.gotToLike = false;
    },
  },
};
</script>