<template>
  <div class="article-detail container">
    <div class="article-detail__information">
      <article-item :article="article" :key="article.id">
        <div slot="meta" class="article__links article-detail__meta">
          <a class="article__link" :href="article.oUrl" target="_blank">原文链接</a>
          <a class="article__link" :href="`https://github.com/xitu/gold-miner/tree/master/TODO/${article.file}`" target="_blank">Markdown 文件</a>
        </div>
        <div slot="footer" class="article__tags">
          <span class="article__tag">推荐于 {{ article.oCdate }}</span>
          <span class="article__tag">{{ article.category }}</span>
          <span class="article__tag">翻译时间：{{ article.tduration }} 天</span>
          <span class="article__tag">校对时间：{{ article.rduration }} 天</span>
          <span class="article__tag">翻译积分：{{ article.tscore }}</span>
          <span class="article__tag">校对积分：{{ article.rscore }}</span>
        </div>
      </article-item>
      <div class="article-detail__toolbar clearfix">
        <el-button class="article-detail__toolbtn pull-right" type="primary" @click="claimTranslation" :loading="loading">{{ mapStatusToText(article.status) || '加载中' }}</el-button>
        <el-button class="article-detail__toolbtn pull-right" @click="showDialog()" v-if="currentUser.admin">编辑</el-button>
      </div>
    </div>
    <div class="timeline">
      <div class="timeline__header">
        <div class="timeline__divider"></div>
        <h3 class="timeline__title">时间线</h3>
      </div>
      <div class="timeline__body">
        <timeline :data="article.timeline"></timeline>
      </div>
    </div>
    <el-dialog title="编辑文章" :visible.sync="dialog.isVisible" @close="closeDialog()">
      <el-form :model="dialog.data" label-width="140px">
        <el-form-item label="文章标题">
          <el-input v-model="dialog.data.title"></el-input>
        </el-form-item>
        <el-form-item label="文章封面">
          <el-input v-model="dialog.data.poster"></el-input>
        </el-form-item>
        <el-form-item label="文章简介">
          <el-input type="textarea" v-model="dialog.data.description"></el-input>
        </el-form-item>
        <el-form-item label="翻译时间">
          <el-input-number v-model="dialog.data.tduration" :min="1"></el-input-number>
        </el-form-item>
        <el-form-item label="翻译积分">
          <el-input-number v-model="dialog.data.tscore" :min="1"></el-input-number>
        </el-form-item>
        <el-form-item label="校对时间">
          <el-input-number v-model="dialog.data.rduration" :min="1"></el-input-number>
        </el-form-item>
        <el-form-item label="校对积分">
          <el-input-number v-model="dialog.data.rscore" :min="1"></el-input-number>
        </el-form-item>
        <el-form-item label="文章单词量">
          <el-input-number v-model="dialog.data.word" :min="0"></el-input-number>
        </el-form-item>
      </el-form>
      <div slot="footer" class="dialog-footer">
        <el-button @click="closeDialog()">取 消</el-button>
        <el-button type="primary" @click="saveChange()" :loading="dialog.loading">确 定</el-button>
      </div>
    </el-dialog>
  </div>
</template>

<script>
import { mapState, mapGetters } from 'vuex'
import pick from 'lodash/pick'
import assign from 'lodash/assign'
import * as articleServices from '@/services/articles'

export default {
  name: 'ArticleDetail',
  data() {
    return {
      article: {
        recommender: {},
        translator: {},
      },
      loading: false,
      dialog: {
        isVisible: false,
        loading: false,
        data: {
          title: '',
          poster: '',
          description: '',
          tduration: 1,
          tscore: 1,
          rduration: 1,
          rscore: 1,
          word: 0,
        },
      },
    }
  },
  computed: {
    ...mapState(['articles']),
    ...mapGetters(['currentUser', 'logIn']),
  },
  methods: {
    mapStatusToText(status) {
      const texts = {
        0: '认领翻译',
        1: '正在翻译',
        2: '认领校对',
        3: '正在校对',
        4: '阅读全文',
      }

      return texts[status]
    },

    showDialog() {
      this.dialog.isVisible = true

      assign(this.dialog.data, pick(this.article, [
        'poster',
        'description',
        'tduration',
        'tscore',
        'rduration',
        'rscore',
        'word',
      ]))
    },

    closeDialog() {
      this.dialog.isVisible = false
    },

    saveChange() {
      this.dialog.loading = true

      const data = assign(this.dialog.data, {
        uid: this.currentUser.id,
      })

      articleServices.updateArticleWithId(this.article.id, data, true).then(() => {
        this.dialog.loading = false
        this.closeDialog()
        assign(this.article, this.dialog.data)
        return this.$message.success('更新成功')
      }).catch((err) => {
        this.dialog.loading = false
        return this.$message.error(err.message)
      })
    },

    claimTranslation() {
      let feedback = ''
      let taskUrl = ''
      let action

      if (!this.logIn) {
        return this.$message.error('请先登录再来认领吧~')
      }

      if (!this.currentUser.translator) {
        return this.$message.error('只有我们的译者才能认领任务哟，快来加入我们吧。')
      }

      if (this.article.status === 0) {
        action = articleServices.claimTranslation({
          id: this.article.id,
          uid: this.currentUser.id,
        })

        feedback = `该文章的 Markdown 文件名为 ${this.article.file}，你可以在我们的 GitHub 仓库中找到该文件，如果你不知道如何操作，请参考 github.com/xitu/gold-miner/wiki 中的相关教程，或联系管理员。`
        taskUrl = `https://github.com/xitu/gold-miner/tree/master/TODO/${this.article.file}`
      } else if (this.article.status === 2) {
        action = articleServices.claimReview({
          id: this.article.id,
          uid: this.currentUser.id,
        })

        feedback = `该文章的校对地址为 github.com/xitu/gold-miner/pull/${this.article.pr}，如果你不知道如何操作，请参考 github.com/xitu/gold-miner/wiki 中的相关教程，或联系管理员。`
        taskUrl = `https://github.com/xitu/gold-miner/pull/${this.article.pr}`
      } else {
        return null
      }

      this.loading = true

      return action.then(() => {
        this.loading = false

        this.$alert(feedback, '申请成功', {
          type: 'success',
          confirmButtonText: '查看详情',
          callback: (action) => {
            if (action === 'confirm') {
              window.open(taskUrl)
            }
          },
        })
      }).catch((err) => {
        this.loading = false
        this.$message.error(err.message)
      })
    },
  },
  created() {
    const { id } = this.$route.params
    const article = this.articles.data[id]

    if (article) {
      this.article = article
      return
    }

    articleServices.fetchArticleWithId(id).then((data) => {
      this.article = data
    }).catch(err => this.$message.error(err.message))
  },
}
</script>

<style lang="scss" scoped>
@import "~styles/exports";

.article-detail {
  width: 80%;

  &__information {
    padding: 20px;
    border: 1px solid $gray-extra-light;
    border-radius: 6px;
    background-color: $drak-white;
  }

  &__meta {
    margin-bottom: 10px;
  }

  &__icon {
    font-size: 24px;
  }

  &__toolbar {
    margin-top: 30px;
  }

  &__toolbtn {
    margin-left: 10px;
  }
}

.timeline {
  position: relative;
  margin-top: 60px;

  &__divider {
    position: absolute;
    top: 15px;
    z-index: -1;
    width: 100%;
    height: 1px;
    background: $gray-light;
  }

  &__title {
    display: inline-block;
    margin: 0;
    padding-right: 10px;
    background-color: #fff;
  }

  &__body {
    padding: 30px 40px;
  }
}
</style>
