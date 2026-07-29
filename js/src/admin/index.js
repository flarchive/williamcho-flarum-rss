import app from 'flarum/admin/app';
import Extend from 'flarum/common/extenders';

export default [
  new Extend.Admin()
    .setting(() => ({
      setting: 'williamcho-rss.max_items',
      type: 'number',
      label: app.translator.trans('williamcho-rss.admin.settings.max_items_label'),
      help: app.translator.trans('williamcho-rss.admin.settings.max_items_help'),
    }))
    .setting(() => ({
      setting: 'williamcho-rss.summary_length',
      type: 'number',
      label: app.translator.trans('williamcho-rss.admin.settings.summary_length_label'),
      help: app.translator.trans('williamcho-rss.admin.settings.summary_length_help'),
    })),
];
